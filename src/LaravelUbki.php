<?php

    namespace Vt2\LaravelUbki;

    use Carbon\Carbon;
    use GuzzleHttp\Client;
    use GuzzleHttp\Psr7\Request;
    use SimpleXMLElement;
    use Vt2\LaravelUbki\Models\UbkiToken;

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

        /**
         * Init
         */
        public function __construct()
        {
            if (config('ubki.account_login') != null) {
                $this->_account_login = config('ubki.account_login');
            }

            if (config('ubki.account_password') != null) {
                $this->_account_password = config('ubki.account_password');
            }

            if (config('ubki.test_mode') == true) {
                if (config('ubki.test_request_url') != null) {
                    $this->_request_url = config('ubki.test_request_url');
                }
                if (config('ubki.test_auth_url') != null) {
                    $this->_auth_url = config('ubki.test_auth_url');
                }
            } else {
                if (config('ubki.request_url') != null) {
                    $this->_request_url = config('ubki.request_url');
                }
                if (config('ubki.auth_url') != null) {
                    $this->_auth_url = config('ubki.auth_url');
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
         *                ]
         *
         * @return mixed
         */
        public function getReport($attributes, $params = [])
        {
            $this->_attributes = $attributes;
            $this->_reason_key = LaravelUbki::REASON_CREDIT;
            $this->_request_id = time();
            $report_alias      = null;

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

            $result = $this->_queryXml();

            if ($result['status'] == 'error' && $result['errors']['errtype'] == $this::ERROR_BAD_TOKEN) {
                UbkiToken::where('token', $this->_session_key)->first()->delete();
                $this->_session_key = '';
            }

            $auth = $this->getSessionKey();
            if ($auth['status'] == 'success') {
                $this->_session_key = $auth['token'];
            }

            $this->_request_xml = $this->_getXml($report_alias);

            return $this->_queryXml();
        }

        /**
         * Get Session Key from UBKI
         *
         * @return mixed
         */
        public function getSessionKey()
        {
            $ubki = UbkiToken::where('created_at', '>', Carbon::now()->startOfDay()->toDateTimeString())
                ->where('token', '!=', null)->get()->last();

            if ($ubki) {
                return ['status' => 'success', 'token' => $ubki->token];
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
                    return ['status' => 'error', 'errors' => $result];
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


    }