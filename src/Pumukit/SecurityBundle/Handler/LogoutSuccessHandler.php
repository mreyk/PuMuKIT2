<?php

namespace Pumukit\SecurityBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Pumukit\SecurityBundle\Services\CASService;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private $options;
    private $router;
    protected $casService;

    public function __construct(array $options, UrlGeneratorInterface $router, CASService $casService)
    {
        $this->options = $options;
        $this->router = $router;
        $this->casService = $casService;
    }

    public function onLogoutSuccess(Request $request)
    {
        $url = $this->router->generate('pumukit_webtv_index_index', array(), true);
        $this->casService->logoutWithRedirectService($url);

        /* Call CAS API to do authentication */
        /*
        \phpCAS::client($this->options['cas_protocol'], $this->options['cas_server'], $this->options['cas_port'], $this->options['cas_path'], false);
        if (!isset($this->options['cas_logout']) || empty($this->options['cas_logout'])) {
          \phpCAS::logout();
        } else {
          // generate absolute URL
          $url = $this->router->generate($this->options['cas_logout'], array(), true);
          \phpCAS::logoutWithRedirectService($url);
        }
        return null;
        */
    }
}
