<?php

    namespace IdnoPlugins\IndiePub\Pages\MicroPub {

        use Idno\Common\ContentType;
        use Idno\Entities\User;

        class Endpoint extends \Idno\Common\Page
        {

            function get()
            {

                echo '?';

            }

            function post()
            {

                $headers = getallheaders();
                $user    = \Idno\Entities\User::getOne(['admin' => true]);
                \Idno\Core\site()->session()->refreshSessionUser($user);
                $indieauth_tokens = $user->indieauth_tokens;

                if (!empty($headers['Authorization'])) {
                    $token = $headers['Authorization'];
                    $token = trim(str_replace('Bearer', '', $token));
                } else if ($token = $this->getInput('access_token')) {
                    $token = trim($token);
                }

                if (!empty($indieauth_tokens[$token])) {

                    // If we're here, we're authorized

                    // Get details
                    $type        = $this->getInput('h');
                    $content     = $this->getInput('content');
                    $name        = $this->getInput('name');
                    $in_reply_to = $this->getInput('in-reply-to');
                    $syndicate   = $this->getInput('syndicate-to');

                    if ($type == 'entry') {
                        if (!empty($_FILES['photo'])) {
                            $type = 'photo';
                            if (empty($name) && !empty($content)) {
                                $name = $content; $content = '';
                            }
                        } else if (empty($name)) {
                            $type = 'note';
                        } else {
                            $type = 'article';
                        }
                    }

                    // Get an appropriate plugin, given the content type
                    if ($contentType = ContentType::getRegisteredForIndieWebPostType($type)) {

                        if ($entity = $contentType->createEntity()) {

                            $this->setInput('title', $name);
                            $this->setInput('body', $content);
                            $this->setInput('inreplyto', $in_reply_to);
                            if (!empty($syndicate)) {
                                $syndication = [trim(str_replace('.com', '', $syndicate))];
                                $this->setInput('syndication', $syndication);
                            }
                            if ($entity->saveDataFromInput()) {
                                //$this->setResponse(201);
                                header('Location: ' . $entity->getURL());
                                exit;
                            } else {
                                $this->setResponse(404);
                                echo "Couldn't create {$type}";
                                exit;
                            }

                        }

                    } else {

                        echo "Couldn't find entry";

                    }

                }

                $this->setResponse(404);
                echo 'Huh?';

            }

        }

    }