<?php

use Base\Log;
use Base\NewLog;
use Tools\Tools;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

class UserPlugin extends Plugin_Abstract
{
    public function preDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        $msg = [
            'Module' => $request->getModuleName(),
            'Controller' => $request->getControllerName(),
            'Action' => $request->getActionName(),
            'Method' => $request->getMethod(),
            'Uri' => $request->getRequestUri(),
        ];

        if ($msg['Method'] == 'POST')
        {
//            $msg['Params'] = array_keys($request->getPost());
            $msg['Params'] = $request->getPost();
        }

        if ($msg['Method'] == 'GET')
        {
            $msg['Params'] = $request->getQuery();
        }

        if ($msg['Method'] == 'CLI') {
            $msg['Params'] = $request->getParams();
        }

        $_SERVER['customTag'] = $request->getQuery('tid', Tools::md5());

        if (!in_array(strtolower($msg['Action']), ['spyrecordposconsumer', 'poslog', 'errorlog', 'spylogconsumer', 'retrycli']))
        {
            NewLog::accessLog(json_encode($msg));
//            Log::accessLog(json_encode($msg));
        }
    }
}