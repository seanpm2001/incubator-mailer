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
use Phalcon\Incubator\Mailer\Message;

final class ManagerCest
{
    private $mailer;

    public function __construct()
    {
        $di = new DI();

        $config = [
            'driver'     => 'smtp',
            'host'       => '127.0.0.1',
            'port'       => getenv('DATA_MAILHOG_PORT'),
            'username'   => 'example@gmail.com',
            'password'   => 'your_password',
            'from'       => [
                'email' => 'example@gmail.com',
                'name'  => 'YOUR FROM NAME',
            ],
        ];

        $this->mailer = new Manager($config);
    }

    public function mailerManagerSendMessage(FunctionalTester $I)
    {
        $message = $this->mailer->createMessage()
            ->to('example_to@gmail.com')
            ->subject('Hello world!')
            ->content('Hello world!');

        $message->send();

        $opts = array(
            'http'=>array(
              'method'=>"GET",
              'header'=>"Accept-language: en\r\n" .
                        "Cookie: foo=bar\r\n"
            )
          );
          
          $context = stream_context_create($opts);
          
          // Accès à un fichier HTTP avec les entêtes HTTP indiqués ci-dessus
          $file = file_get_contents('http://127.0.0.1:'.getenv('DATA_MAILHOG_PORT').'/api/v1/messages', false, $context);
          var_dump($file);die;
    }
}
