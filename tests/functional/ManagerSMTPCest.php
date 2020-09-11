<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Incubator\Mailer\Tests\Functional\Manager;

use FunctionalTester;
use Phalcon\Incubator\Mailer\Manager;
use Phalcon\Di\FactoryDefault as DI;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;

final class ManagerSMTPCest
{
    private $config;

    public function __construct()
    {
        $di = new DI();

        $this->config = [
            'driver'     => 'smtp',
            'host'       => '127.0.0.1',
            'port'       => getenv('DATA_MAILHOG_PORT'),
            'username'   => 'example@gmail.com',
            'password'   => 'your_password',
            'from'       => [
                'email' => 'example@gmail.com',
                'name'  => 'YOUR FROM NAME',
            ],
            //'viewsDir'   => codecept_data_dir() . '/fixtures/views/'
        ];

        $di->setShared('view', function () {
            $view = new View();
            $view->setDI($this);
            $view->setViewsDir( '../_data/fixtures/views/');

            $view->registerEngines([
                '.volt'  => function ($view) {

                    $volt = new VoltEngine($view, $this);

                    $volt->setOptions([
                        'path'      => codecept_output_dir(),
                        'separator' => '_'
                    ]);

                    return $volt;
                },
                '.phtml' => PhpEngine::class

            ]);

            return $view;
        });

    }

    public function mailerManagerCreateMessage(FunctionalTester $I)
    {
        $to      = 'example_to@gmail.com';
        $subject = 'Hello World';
        $body    = 'Lorem Ipsum';

        $mailer = new Manager($this->config);
        
        $message = $mailer->createMessage()
            ->to($to)
            ->subject($subject)
            ->content($body);

        $message->send();

        $opts = [
            'http' => [
              'method' => 'GET',
              'header' => 'Accept-language: en\r\n'
            ]
        ];
          
        $context = stream_context_create($opts);
        
        // Get all mail send in the MailHog SMTP
        $baseUrl = 'http://127.0.0.1:' . getenv('DATA_MAILHOG_CHECK_PORT') . '/api/v1/';
        $dataMail = file_get_contents($baseUrl . 'messages', false, $context);
        $dataMail = \json_decode($dataMail);
        
        //Check that there are one mail send
        $I->assertCount(1, $dataMail);

        $mail = end($dataMail);
    
        $mailFromData = $mail->From;
        $mailToData   = end($mail->To);
        
        $mailFrom = $mailFromData->Mailbox . '@' . $mailFromData->Domain;
        $mailTo   = $mailToData->Mailbox . '@' . $mailToData->Domain;

        $I->assertEquals($mailFrom, $this->config['from']['email']);
        $I->assertEquals($mailTo, $to);

        $I->assertEquals($mail->Content->Body, $body);
        $I->assertStringContainsString('Subject: ' . $subject, $mail->Raw->Data);
    }

    public function mailerManagerCreateMessageFromView(FunctionalTester $I)
    {
        $mailer = new Manager($this->config);

        // view relative to the folder viewsDir (REQUIRED)
        $viewPath = 'mail/signup';

        // Set variables to views (OPTIONAL)
        $params = [
            'var1' => 'VAR VALUE 1',
            'var2' => 'VAR VALUE 2',
        ];

        $body = '<b>' . strtoupper($params['var1']) . '</b>
        <b>' . strtoupper($params['var2']) . '</b>';

        $to      = 'example_to@gmail.com';
        $subject = 'Hello World';

        $message = $mailer->createMessageFromView($viewPath, $params)
                ->to($to)
                ->subject($subject);
        $message->send();

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Accept-language: en\r\n'
            ]
        ];
          
        $context = stream_context_create($opts);
        
        // Get all mail send in the MailHog SMTP
        $baseUrl = 'http://127.0.0.1:' . getenv('DATA_MAILHOG_CHECK_PORT') . '/api/v1/';
        $dataMail = file_get_contents($baseUrl . 'messages', false, $context);
        $dataMail = \json_decode($dataMail);
        
        //Check that there are one mail send
        $I->assertCount(1, $dataMail);

        $mail = end($dataMail);
    
        $mailFromData = $mail->From;
        $mailToData   = end($mail->To);
        
        $mailFrom = $mailFromData->Mailbox . '@' . $mailFromData->Domain;
        $mailTo   = $mailToData->Mailbox . '@' . $mailToData->Domain;

        $I->assertEquals($mailFrom, $this->config['from']['email']);
        $I->assertEquals($mailTo, $to);

        $I->assertEquals($mail->Content->Body, $body);
        $I->assertStringContainsString('Subject: ' . $subject, $mail->Raw->Data);
    }
}
