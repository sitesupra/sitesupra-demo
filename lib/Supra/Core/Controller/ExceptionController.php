<?php

namespace Supra\Core\Controller;

use Symfony\Component\HttpFoundation\Response;

class ExceptionController extends Controller
{
	public function exception404Action(\Exception $e)
	{
		return new Response(
			$this->exceptionAction(404, 'Not found'),
			404
		);
	}

	public function exception500Action(\Exception $e)
	{
		return new Response(
			$this->exceptionAction(500, 'Internal server error'),
			500
		);
	}

	protected function exceptionAction($code, $message)
	{
		$output = <<<CODE
<!DOCTYPE html>

<html>
    <head>
        <meta charset="utf-8">
        <meta name="Generator" content="SiteSupra&reg; http://www.sitesupra.com" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="favicon.ico" rel="icon shortcut">
        <link rel="icon" type="image/png" href="/favicon.png" /><!--[if lte IE 8]>
        <link rel="shortcut icon" href="/favicon-simple.ico" type="image/vnd.microsoft.icon" />
        <![endif]--><!--[if gt IE 8]>
        <link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon" />
        <![endif]-->

        <title>Server exception $code</title>

        <style>
            html, body {
                height: 100%;
                min-height: 100%;
                margin: 0;
                overflow: hidden;
                padding: 0;
                width: 100%;
            }
            body {
                min-height: 435px;
                min-width: 555px;
            }
            .wrapper {
                padding: 10% 0 0 13%;
            }
            .content {
                height: 380px;
                width: 480px;
            }
            .content > * {
                margin: 0;
                padding: 0;
            }
            h1 {
                font: bold 200px/200px "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
                color: #b32322;
            }
            h2 {
                color: #292c37;
                font: normal 30px/30px "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
            }
            p {
                color: #8f8f91;
                font: normal 16px/24px "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
            }
            p.info {
                height: 130px;
                margin-top: 30px;
            }
            a {
                color: #292c37;
            }
            a:hover {
                color: #b32322;
            }
        </style>
    </head>

    <body>
        <div class="wrapper">
            <div class="content">
                <h1>$code</h1>
                <h2>$message.</h2>
                <p class="info">Something has gone wrong. That's all we know.</p>
                <p class="back">Back to <a href="/">home page</a>.</p>
            </div>
        </div>
    </body>
</html>
CODE;
		return $output;
	}

}
