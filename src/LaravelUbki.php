<?php

    namespace Artjoker\LaravelUbki;

    use Carbon\Carbon;
    use GuzzleHttp\Client;
    use GuzzleHttp\Psr7\Request;
    use SimpleXMLElement;
    use Artjoker\LaravelUbki\Models\UbkiToken;

    class LaravelUbki
    {
        const ERROR_BAD_TOKEN = 16; // Неверный или устаревший сессионный ключ

        const REASON_UPLOAD = 0; // Передача кредитных историй в УБКИ
        const REASON_CREDIT = 2; // Заявка на кредит

        const LANG_SEARCH_COD = ['uk' => 1, 'ru' => 2]; // Языки поиска

        private $_account_login;
        private $_account_password;
        private $_request_url;
        private $_auth_url;
        private $_request_xml;
        private $_response_xml;
        private $_session_key;
        private $_reason_key;
        private $_request_id;
        private $_attributes;
        private $_lang_search;
        private $_request_data;
        private $_upload_url;
        private $_req_type;
        private $_upload = false;
        private $_reload_session = false;

        /**
         * Init
         */
        public function __construct()
        {
            if (config('ubki.test_mode') == true) {
                if (config('ubki.test_account_login') != null) {
                    $this->_account_login = config('ubki.test_account_login');
                }
                if (config('ubki.test_account_password') != null) {
                    $this->_account_password = config('ubki.test_account_password');
                }
                if (config('ubki.test_request_url') != null) {
                    $this->_request_url = config('ubki.test_request_url');
                }
                if (config('ubki.test_auth_url') != null) {
                    $this->_auth_url = config('ubki.test_auth_url');
                }
                if (config('ubki.test_upload_url') != null) {
                    $this->_upload_url = config('ubki.test_upload_url');
                }
            } else {
                if (config('ubki.account_login') != null) {
                    $this->_account_login = config('ubki.account_login');
                }
                if (config('ubki.account_password') != null) {
                    $this->_account_password = config('ubki.account_password');
                }
                if (config('ubki.request_url') != null) {
                    $this->_request_url = config('ubki.request_url');
                }
                if (config('ubki.auth_url') != null) {
                    $this->_auth_url = config('ubki.auth_url');
                }
                if (config('ubki.upload_url') != null) {
                    $this->_upload_url = config('ubki.upload_url');
                }
            }
            $this->_lang_search = config('ubki.lang_default');
            UbkiToken::where('created_at', '<', Carbon::now()->startOfDay()->toDateTimeString())->delete();
        }

        /**
         * Get report from UBKI
         *
         * @param $attributes
         * @param $params = [
         *                'report',      // alias of the type of report
         *                'request_id',  // Request ID from our side (if necessary)
         *                'lang'         // Language of search
         *                'test'         // Enables the Test Mode
         *                ]
         *
         * @return mixed
         */
        public function getReport($attributes, $params = [])
        {
            if (isset($params['test'])) {
                if ($params['test'] == true) {
                    if (config('ubki.test_account_login') != null) {
                        $this->_account_login = config('ubki.test_account_login');
                    }
                    if (config('ubki.test_account_password') != null) {
                        $this->_account_password = config('ubki.test_account_password');
                    }
                    if (config('ubki.test_request_url') != null) {
                        $this->_request_url = config('ubki.test_request_url');
                    }
                    if (config('ubki.test_auth_url') != null) {
                        $this->_auth_url = config('ubki.test_auth_url');
                    }
                } else {
                    if (config('ubki.account_login') != null) {
                        $this->_account_login = config('ubki.account_login');
                    }
                    if (config('ubki.account_password') != null) {
                        $this->_account_password = config('ubki.account_password');
                    }
                    if (config('ubki.request_url') != null) {
                        $this->_request_url = config('ubki.request_url');
                    }
                    if (config('ubki.auth_url') != null) {
                        $this->_auth_url = config('ubki.auth_url');
                    }
                }
            }

            $this->_attributes = $attributes;
            $this->_reason_key = LaravelUbki::REASON_CREDIT;
            $this->_request_id = time();
            $report_alias      = null;
            $this->_upload     = false;

            if (isset($params['report'])) {
                $report_alias = $params['report'];
            }
            if (isset($params['request_id'])) {
                $this->_request_id = $params['request_id'];
            }
            if (isset($params['lang'])) {
                $this->_lang_search = $params['lang'];
            }

            $auth = $this->getSessionKey();
            if ($auth['status'] == 'success') {
                $this->_session_key = $auth['token'];
            }

            $this->_request_xml = $this->_getXml($report_alias);
            $result             = $this->_queryXml();

            if ($result['status'] == 'error' && $result['errors']['errtype'] == $this::ERROR_BAD_TOKEN) {
                //UbkiToken::where('token', $this->_session_key)->first()->delete();
                $this->_session_key = '';
                $this->_reload_session = true;

                $auth = $this->getSessionKey();
                if ($auth['status'] == 'success') {
                    $this->_session_key = $auth['token'];
                }
                $this->_reload_session = false;

                $this->_request_xml = $this->_getXml($report_alias);
                $result             = $this->_queryXml();
            }

            return $result;
        }

        /**
         * Get Session Key from UBKI
         *
         * @return mixed
         */
        public function getSessionKey()
        {
            if($this->_reload_session == false){
                $ubki = UbkiToken::where('created_at', '>', Carbon::now()->startOfDay()->toDateTimeString())
                    ->where('token', '!=', null)->get()->last();

                if ($ubki) {
                    return ['status' => 'success', 'token' => $ubki->token];
                }
            }
            $this->_getSessionKey();
            $result = $this->_parseXml();

            if (isset($result['errcode'])) {
                UbkiToken::create([
                    'token'      => null,
                    'error_code' => $result['errcode'],
                    'response'   => $this->_response_xml,
                ]);
                return ['status' => 'error', 'errors' => $result];
            }

            if (isset($result['sessid'])) {
                UbkiToken::create([
                    'token'      => $result['sessid'],
                    'error_code' => null,
                    'response'   => $this->_response_xml,
                ]);
                return ['status' => 'success', 'token' => $result['sessid'], 'response' => $result];
            }
            return false;
        }

        /**
         * Get session key from UBKI
         *
         * @return mixed
         * @throws
         */
        private function _getSessionKey()
        {
            $this->_request_xml = '<?xml version="1.0" encoding="utf-8" ?><doc>' .
                '<auth login="' . $this->_account_login . '" pass="' . $this->_account_password . '"/></doc>';

            $client  = new Client();
            $request = new Request(
                'POST',
                $this->_auth_url,
                ['Content-Type' => 'text/xml; charset=UTF8'],
                base64_encode($this->_request_xml)
            );

            $response            = $client->send($request);
            $this->_response_xml = $response->getBody();
            return true;
        }

        /**
         * Send a request to UBKI and get a response
         *
         * @return mixed
         * @throws
         */
        private function _queryXml()
        {
            if ($this->_request_url && $this->_request_xml) {
                $client              = new Client();
                $request             = new Request(
                    'POST',
                    $this->_request_url,
                    ['Content-Type' => 'text/xml; charset=UTF8'],
                    $this->_request_xml
                );
                $response            = $client->send($request);
                $this->_response_xml = $response->getBody();
                $result              = $this->_parseXml();

                if (isset($result['errtype'])) {
                    $res = new SimpleXMLElement($this->_response_xml);
                    return ['status' => 'error', 'errors' => $result, 'request_data' => $this->_request_data, 'response_data' => $res];
                } else {
                    return ['status' => 'success', 'response' => $response->getBody()];
                }
            }
            return false;
        }

        /**
         * Parsing the response from UBKI
         *
         * @return mixed
         * @throws
         */
        private function _parseXml()
        {
            $response = [];
            $res      = new SimpleXMLElement($this->_response_xml);

            if ($this->_upload == false) {
                if (isset($res->auth)) {
                    foreach ($res->auth->attributes() as $key => $attr) {
                        $response[$key] = (string)$attr;
                    }
                } else {
                    if (isset($res->tech->error)) {
                        foreach ($res->tech->error->attributes() as $key => $attr) {
                            $response[$key] = (string)$attr;
                        }
                    }
                }
            } else {
                if (isset($res->auth)) {
                    foreach ($res->auth->attributes() as $key => $attr) {
                        $response[$key] = (string)$attr;
                    }
                } else {
                    if (isset($res->tech->error)) {
                        foreach ($res->tech->error->attributes() as $key => $attr) {
                            $response[$key] = (string)$attr;
                        }
                    } else {
                        if (isset($res->tech->sentdatainfo)) {
                            foreach ($res->tech->sentdatainfo->attributes() as $key => $attr) {
                                $response[$key] = (string)$attr;
                            }
                        }
                        if (isset($response['state'])) {
                            if ($response['state'] == 'ok' || $response['state'] == 'nt') {
                                $response['status'] = 'success';
                                return $response;
                            }
                            if ($response['state'] == 'er') {
                                $response['status'] = 'error';
                                if (isset($res->tech->item)) {
                                    foreach ($res->tech->item->attributes() as $key => $attr) {
                                        $response[$key] = (string)$attr;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $response;
        }


        /**
         * Get xml for the request to UBKI
         *
         * @param $report_alias
         *
         * @return string
         */
        private function _getXml($report_alias)
        {
            if ($report_alias == null) {
                $report_alias = config('ubki.report_default');
            }

            switch ($report_alias) {
                case 'standard':
                    return $this->_prepare(config('ubki.reports.standard'));
                    break;
                case 'standard_pb':
                    return $this->_prepare(config('ubki.reports.standard_pb'));
                    break;
                case 'contacts':
                    return $this->_prepare(config('ubki.reports.contacts'));
                    break;
                case 'scoring':
                    return $this->_prepare(config('ubki.reports.scoring'));
                    break;
                case 'identification':
                    return $this->_prepare(config('ubki.reports.identification'));
                    break;
                case 'passport':
                    return $this->_prepare(config('ubki.reports.passport'));
                    break;
            }
        }

        /**
         * Create xml for the request to UBKI
         *
         * @param  $cod_report
         *
         * @return string
         */
        private function _prepare($cod_report)
        {
            $req_request = '<request version="1.0" '
                . 'reqtype="' . $cod_report . '" '
                . 'reqreason="' . $this->_reason_key . '" '
                . 'reqdate="' . Carbon::now()->format('Y-m-d') . '" '
                . 'reqidout="' . $this->_request_id . '" '
                . 'reqsource="1">'
                . '<i reqlng="' . config('ubki.languages.' . $this->_lang_search) . '">'
                . '<ident 
                        okpo="' . $this->_attributes[config('ubki.model_data.okpo')] . '"
                        lname="' . $this->_attributes[config('ubki.model_data.lname')] . '"
                        fname="' . $this->_attributes[config('ubki.model_data.fname')] . '"
                        mname="' . $this->_attributes[config('ubki.model_data.mname')] . '"
                        bdate="' . $this->_attributes[config('ubki.model_data.bdate')] . '"
                        orgname=""
                     ></ident>';

            if ($cod_report != config('ubki.reports.passport')) {
                $req_request .= '<spd inn="' . $this->_attributes[config('ubki.model_data.okpo')] . '" />';

                $req_request .= '<docs><doc 
                        dtype="' . $this->_attributes[config('ubki.model_data.dtype')] . '"
                        dser="' . $this->_attributes[config('ubki.model_data.dser')] . '"
                        dnom="' . $this->_attributes[config('ubki.model_data.dnom')] . '"
                    /></docs>';

                $req_request .= '<contacts><cont
                        ctype = "' . $this->_attributes[config('ubki.model_data.ctype')] . '"
                        cval  = "' . $this->_attributes[config('ubki.model_data.cval')] . '"
                    /></contacts >';
            }

            if ($cod_report == config('ubki.reports.standard') ||
                $cod_report == config('ubki.reports.standard_pb') ||
                $cod_report == config('ubki.reports.passport')) {
                $req_request .= '<mvd
                        pser   = "' . $this->_attributes[config('ubki.model_data.dser')] . '"
                        pnom   = "' . $this->_attributes[config('ubki.model_data.dnom')] . '"
                        plname = "' . $this->_attributes[config('ubki.model_data.lname')] . '"
                        pfname = "' . $this->_attributes[config('ubki.model_data.fname')] . '"
                        pmname = "' . $this->_attributes[config('ubki.model_data.mname')] . '"
                        pbdate = "' . $this->_attributes[config('ubki.model_data.bdate')] . '"
                ></mvd >';
            }

            $req_request .= '</i></request>';

            $this->_request_data = $req_request;

            return $req_xml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<doc>'
                . '<ubki sessid="' . $this->_session_key . '">'
                . '<req_envelope>'
                . '<req_xml>'
                . base64_encode($req_request)
                . '</req_xml>'
                . '</req_envelope>'
                . '</ubki>'
                . '</doc>';
        }

        /**
         * Send the report to UBKI
         *
         * @param $attributes
         * @param $params = [
         *                'request_id',     // Request ID from our side (if necessary)
         *                'upload_req_type' // upload_req_type (optional)
         *                'lang'            // Language of upload (optional)
         *                'test'            // Enables the Test Mode
         *                ]
         *
         * @return mixed
         */
        public function sendReport($attributes, $params = [])
        {
            if (isset($params['test'])) {
                if ($params['test'] == true) {
                    if (config('ubki.test_account_login') != null) {
                        $this->_account_login = config('ubki.test_account_login');
                    }
                    if (config('ubki.test_account_password') != null) {
                        $this->_account_password = config('ubki.test_account_password');
                    }
                    if (config('ubki.test_upload_url') != null) {
                        $this->_upload_url = config('ubki.test_upload_url');
                    }
                    if (config('ubki.test_auth_url') != null) {
                        $this->_auth_url = config('ubki.test_auth_url');
                    }
                } else {
                    if (config('ubki.account_login') != null) {
                        $this->_account_login = config('ubki.account_login');
                    }
                    if (config('ubki.account_password') != null) {
                        $this->_account_password = config('ubki.account_password');
                    }
                    if (config('ubki.upload_url') != null) {
                        $this->_upload_url = config('ubki.upload_url');
                    }
                    if (config('ubki.auth_url') != null) {
                        $this->_auth_url = config('ubki.auth_url');
                    }
                }
            }
            $this->_attributes = $attributes;
            $this->_reason_key = LaravelUbki::REASON_UPLOAD;
            $this->_request_id = time();
            $this->_upload     = true;

            $this->_req_type = config('ubki.upload_req_type');
            if (isset($params['upload_req_type'])) {
                $this->_req_type = $params['upload_req_type'];
            }

            if (isset($params['request_id'])) {
                $this->_request_id = $params['request_id'];
            }

            if (isset($params['lang'])) {
                $this->_lang_search = $params['lang'];
            }

            $auth = $this->getSessionKey();
            if ($auth['status'] == 'success') {
                $this->_session_key = $auth['token'];
            }

            $this->_request_xml = $this->_getXmlUpload();
            $result             = $this->_queryXmlUpload();

            if ($result['status'] == 'error' && $result['errors']['errtype'] == $this::ERROR_BAD_TOKEN) {
                //UbkiToken::where('token', $this->_session_key)->first()->delete();
                $this->_session_key = '';
                $this->_reload_session = true;

                $auth = $this->getSessionKey();
                if ($auth['status'] == 'success') {
                    $this->_session_key = $auth['token'];
                }
                $this->_reload_session = false;

                $this->_request_xml = $this->_getXmlUpload();
                $result             = $this->_queryXmlUpload();
            }

            return $result;
        }

        /**
         * Send a request to UBKI and get a response
         *
         * @return mixed
         * @throws
         */
        private function _queryXmlUpload()
        {
            if ($this->_upload_url && $this->_request_xml) {
                $client              = new Client();
                $request             = new Request(
                    'POST',
                    $this->_upload_url,
                    ['Accept'       => 'application/xml',
                     'Content-Type' => 'application/xml'],
                    $this->_request_xml
                );
                $response            = $client->send($request);
                $this->_response_xml = $response->getBody();
                $result              = $this->_parseXml();

                if (isset($result['errtype'])) {
                    $res = new SimpleXMLElement($this->_response_xml);
                    return ['status' => 'error', 'errors' => $result, 'request_data' => $this->_request_data, 'response_data' => $res];
                } else {
                    return ['status' => 'success', 'response' => $response->getBody()];
                }
            }
            return false;
        }

        /**
         * Get xml for the uload to UBKI
         *
         * @return string
         */
        private function _getXmlUpload()
        {
            $sex   = (substr($this->_attributes[config('ubki.model_data.okpo')], 8, 1) % 2) ? 1 : 2;
            $vdate = Carbon::parse($this->_attributes[config('ubki.model_data_upload.vdate')])->format('Y-m-d');

            $req_request = '
            <request version="1.0" '
                . 'reqtype="' . $this->_req_type . '" '
                . 'reqreason="' . $this->_reason_key . '" '
                . 'reqdate="' . Carbon::now()->format('Y-m-d') . '" '
                . 'reqidout="' . $this->_request_id . '" '
                . 'reqsource="1">'
                . '<ubkidata>'
                . '<comp id="1">'
                . '<cki inn="' . $this->_attributes[config('ubki.model_data.okpo')] . '" 
                        lname="' . $this->_attributes[config('ubki.model_data.lname')] . '" 
                        fname="' . $this->_attributes[config('ubki.model_data.fname')] . '" 
                        mname="' . $this->_attributes[config('ubki.model_data.mname')] . '" 
                        bdate="' . $this->_attributes[config('ubki.model_data.bdate')] . '" 
                        reqlng="' . config('ubki.languages.' . $this->_lang_search) . '" 
                        reqlngref="">'
                . '<ident inn="' . $this->_attributes[config('ubki.model_data.okpo')] . '" 
                        vdate="' . $vdate . '" 
                        lng="' . config('ubki.languages.' . $this->_lang_search) . '" 
                        lname="' . $this->_attributes[config('ubki.model_data.lname')] . '" 
                        fname="' . $this->_attributes[config('ubki.model_data.fname')] . '" 
                        mname="' . $this->_attributes[config('ubki.model_data.mname')] . '" 
                        bdate="' . $this->_attributes[config('ubki.model_data.bdate')] . '" 
                        csex="' . $sex . '" 
                        cchild="" csexref="" familyref="" ceduc="" ceducref="" cgrag="" cgragref="" lngref="" sstateref="" ';

            if (isset($this->_attributes[config('ubki.model_data_upload.family')])) {
                $req_request .= 'family="' . $this->_attributes[config('ubki.model_data_upload.family')] . '" ';
            }
            if (isset($this->_attributes[config('ubki.model_data_upload.sstate')])) {
                $req_request .= 'sstate="' . $this->_attributes[config('ubki.model_data_upload.sstate')] . '" ';
            }

            $dterm = '';
            $dser  = $this->_attributes[config('ubki.model_data_upload.dser')];
            $dwho  = $this->_attributes[config('ubki.model_data_upload.dwho')];
            if ($this->_attributes[config('ubki.model_data_upload.dtype')] == 17) {
                if ($this->_attributes[config('ubki.model_data_upload.dterm')] != '') {
                    $dterm = Carbon::parse($this->_attributes[config('ubki.model_data_upload.dterm')])->format('Y-m-d');
                }
                if ($dwho == '') {
                    $dwho = '0';
                }
                $dser = '';
            }

            $req_request .= '></ident><doc 
                vdate="' . $vdate . '"  
                lng="' . config('ubki.languages.' . $this->_lang_search) . '" 
                dtype="' . $this->_attributes[config('ubki.model_data_upload.dtype')] . '"
                dser= "' . $dser . '" 
                dnom= "' . $this->_attributes[config('ubki.model_data_upload.dnom')] . '" 
                dwdt="' . $this->_attributes[config('ubki.model_data_upload.dwdt')] . '" 
                dwho="' . $dwho . '" 
                dterm="' . $dterm . '" 
                dtyperef="" lngref=""></doc>';

            $req_request .= '<addr 
                vdate="' . $vdate . '"  
                lng="' . config('ubki.languages.' . $this->_lang_search) . '" 
                adtype="2" lngref="" addrdirt="" adtyperef="" 
                adcountry="' . config('ubki.upload_country') . '" 
                adindex="' . $this->_attributes[config('ubki.model_data_upload.adindex')] . '" 
                adstate="' . $this->_attributes[config('ubki.model_data_upload.adstate')] . '" 
                adarea="" 
                adcity="' . $this->_attributes[config('ubki.model_data_upload.adcity')] . '" 
                adcitytype="" adcitytyperef="" 
                adstreet="' . $this->_attributes[config('ubki.model_data_upload.adstreet')] . '" 
                adhome="' . $this->_attributes[config('ubki.model_data_upload.adhome')] . '" 
                adcorp="" ';
            if (isset($this->_attributes[config('ubki.model_data_upload.adflat')])) {
                $req_request .= 'adflat="' . $this->_attributes[config('ubki.model_data_upload.adflat')] . '" ';
            } else {
                $req_request .= 'adflat="" ';
            }
            $req_request .= '></addr>';

            if (isset($this->_attributes[config('ubki.model_data_upload.adactual')])) {
                if ($this->_attributes[config('ubki.model_data_upload.adactual')] == 1) {
                    $req_request .= '<addr 
                vdate="' . $vdate . '"  
                lng="' . config('ubki.languages.' . $this->_lang_search) . '" 
                adtype="1" lngref="" addrdirt="" adtyperef="" 
                adcountry="' . config('ubki.upload_country') . '" 
                adindex="' . $this->_attributes[config('ubki.model_data_upload.adindex2')] . '" 
                adstate="' . $this->_attributes[config('ubki.model_data_upload.adstate2')] . '" 
                adarea="" 
                adcity="' . $this->_attributes[config('ubki.model_data_upload.adcity2')] . '" 
                adcitytype=""  adcitytyperef="" 
                adstreet="' . $this->_attributes[config('ubki.model_data_upload.adstreet2')] . '" 
                adhome="' . $this->_attributes[config('ubki.model_data_upload.adhome2')] . '" 
                adcorp="" ';
                    if (isset($this->_attributes[config('ubki.model_data_upload.adflat2')])) {
                        $req_request .= 'adflat="' . $this->_attributes[config('ubki.model_data_upload.adflat2')] . '" ';
                    }
                    $req_request .= '></addr>';
                }
            }

            $req_request .= '</cki></comp><comp id="2"><crdeal 
               inn="' . $this->_attributes[config('ubki.model_data.okpo')] . '" 
               dlref="' . $this->_attributes[config('ubki.model_data_upload.dlref')] . '" 
               lng="' . config('ubki.languages.' . $this->_lang_search) . '" lngref="" 
               lname="' . $this->_attributes[config('ubki.model_data.lname')] . '" 
               fname="' . $this->_attributes[config('ubki.model_data.fname')] . '" 
               mname="' . $this->_attributes[config('ubki.model_data.mname')] . '" 
               bdate="' . $this->_attributes[config('ubki.model_data.bdate')] . '" 
               dlcelcred="' . config('ubki.upload_transaction_type') . '" dlcelcredref="" 
               dlvidobes="' . config('ubki.upload_collateral') . '" dlvidobesref="" 
               dlporpog="' . config('ubki.upload_repayment') . '"  dlporpogref="" 
               dlcurr="' . config('ubki.upload_currency') . '" dlcurrref=""  
               dlamt="' . $this->_attributes[config('ubki.model_data_upload.dlamt')] . '" 
               dlrolesub="' . config('ubki.upload_subject') . '" dlrolesubref="" 
               dlamtobes="0" dldonor="">';

            $date_contract   = $this->_attributes[config('ubki.model_data_upload.dlds')];
            $expiration_date = $this->_attributes[config('ubki.model_data_upload.dldpf')];
            $upload_date     = $this->_attributes[config('ubki.model_data_upload.dldateclc')];
            $close_date      = '';
            $status          = $this->_attributes[config('ubki.model_data_upload.dlflstat')];
            if ($status == 2 || $status == 3 || $status == 6 || $status == 7 || $status == 10) {
                $date       = $this->_attributes[config('ubki.model_data_upload.dldff')];
                $close_date = Carbon::parse($date)->format('Y-m-d');
            }
            $dlflpay = 0;
            if ($status == 2 || $status == 3 || $status == 5) {
                $dlflpay = 1;
            }
            $dlamtcur = 0;
            if ($status == 1 || $status == 5) {
                $dlamtcur = $this->_attributes[config('ubki.model_data_upload.dlamtcur')];
            }

            $dlflbrk = $dldayexp = $dlamtexp = 0;
            if (isset($this->_attributes[config('ubki.model_data_upload.dlflbrk')])) {
                $dlflbrk  = 1;
                $dldayexp = $this->_attributes[config('ubki.model_data_upload.dldayexp')];
                $dlamtexp = $this->_attributes[config('ubki.model_data_upload.dlamtcur')];
            }

            $req_request .= '<deallife 
                dlref="' . $this->_attributes[config('ubki.model_data_upload.dlref')] . '" 
                dlmonth="' . Carbon::now()->subMonth()->format('m') . '" 
                dlyear="' . Carbon::now()->subMonth()->format('Y') . '" 
                dlds="' . Carbon::parse($date_contract)->format('Y-m-d') . '" 
                dldpf="' . Carbon::parse($expiration_date)->format('Y-m-d') . '" 
                dldff="' . $close_date . '" 
                dlflstat="' . $status . '" dlflstatref="" 
                dlamtlim="0" 
                dlamtpaym="0" 
                dlamtcur="' . $dlamtcur . '" 
                dlamtexp="' . $dlamtexp . '" 
                dldayexp="' . $dldayexp . '" 
                dlflpay="' . $dlflpay . '" dlflpayref=""
                dlflbrk="' . $dlflbrk . '" dlflbrkref="" 
                dlfluse="0" dlfluseref="Нет" 
                dldateclc="' . Carbon::parse($upload_date)->format('Y-m-d') . '" ></deallife>';

            $req_request .= '</crdeal></comp><comp id="10"><cont 
                inn="' . $this->_attributes[config('ubki.model_data.okpo')] . '" 
                vdate="' . $vdate . '" 
                ctype="3" 
                cval="+380' . $this->_attributes[config('ubki.model_data_upload.cval')] . '" 
                ></cont></comp></ubkidata></request>';

            $req_request = '<?xml version="1.0" encoding="utf-8"?>'
                . '<doc>'
                . '<ubki sessid="' . $this->_session_key . '">'
                . '<req_envelope>'
                . '<req_xml>'
                // . base64_encode($req_request)
                . $req_request
                . '</req_xml>'
                . '</req_envelope>'
                . '</ubki>'
                . '</doc>';

            //dd($req_request);

            $this->_request_data = $req_request;

            return $req_xml = base64_encode($req_request);
        }

    }