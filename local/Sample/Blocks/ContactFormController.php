<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\BlockController;

class ContactFormController extends BlockController
{
    public function doExecute()
    {
        if ($this->getRequest()->isMethod('POST')) {

            $email = $this->getProperty('email')->getValue();

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $request = $this->getRequest();

                $body = implode("\n", array(
                    'Email: '   . $request->request->get('email'),
                    'Name: '    . $request->request->get('name'),
                    'Message:'  . $request->request->get('message'),
                ));

                $mailer = $this->container->getMailer();

                $message = \Swift_Message::newInstance('Contact form submission', $body);

                $message->setTo($email);

                $mailer->send($message);

                $this->getResponse()->assign('messageSent', true);
            }
        }

        $this->getResponse()->render();
    }
}