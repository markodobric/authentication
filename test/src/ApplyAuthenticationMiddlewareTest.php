<?php

/*
 * This file is part of the Active Collab Authentication project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Authentication\Test;

use ActiveCollab\Authentication\Adapter\BrowserSessionAdapter;
use ActiveCollab\Authentication\AuthenticationResult\Transport\Authorization\AuthorizationTransport;
use ActiveCollab\Authentication\Middleware\ApplyAuthenticationMiddleware;
use ActiveCollab\Authentication\Test\AuthenticatedUser\AuthenticatedUser;
use ActiveCollab\Authentication\Test\AuthenticatedUser\Repository as UserRepository;
use ActiveCollab\Authentication\Test\Session\Repository as SessionRepository;
use ActiveCollab\Authentication\Test\Session\Session;
use ActiveCollab\Authentication\Test\TestCase\RequestResponseTestCase;
use ActiveCollab\Cookies\Cookies;
use ActiveCollab\Cookies\CookiesInterface;
use ActiveCollab\ValueContainer\Request\RequestValueContainer;
use ActiveCollab\ValueContainer\ValueContainer;
use ActiveCollab\ValueContainer\ValueContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package ActiveCollab\Authentication\Test
 */
class ApplyAuthenticationMiddlewareTest extends RequestResponseTestCase
{
    /**
     * @var CookiesInterface
     */
    private $cookies;

    /**
     * @var ValueContainerInterface
     */
    private $value_container;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->cookies = new Cookies();
        $this->value_container = new ValueContainer();
    }

    /**
     * Test if authentication is applied based on request attribute.
     */
    public function testUserIsAuthenticated()
    {
        $user = new AuthenticatedUser(1, 'ilija.studen@activecollab.com', 'Ilija Studen', '123');
        $user_repository = new UserRepository([
            'ilija.studen@activecollab.com' => new AuthenticatedUser(1, 'ilija.studen@activecollab.com', 'Ilija Studen', '123'),
        ]);
        $session_repository = new SessionRepository([new Session('my-session-id', 'ilija.studen@activecollab.com')]);

        $session_cookie_name = 'test-session-cookie';

        $session_adapter = new BrowserSessionAdapter($user_repository, $session_repository, $this->cookies, $session_cookie_name);
        $session = $session_adapter->authenticate($user, []);

        /** @var ServerRequestInterface $request */
        $request = $this->request->withAttribute('test_transport', new AuthorizationTransport($session_adapter, $user, $session, [1, 2, 3]));

        $middleware = new ApplyAuthenticationMiddleware(new RequestValueContainer('test_transport'));
        $this->assertFalse($middleware->applyOnExit());

        /** @var ResponseInterface $response */
        $response = call_user_func($middleware, $request, $this->response);

        $this->assertInstanceOf(ResponseInterface::class, $response);

        $set_cookie_header = $response->getHeaderLine('Set-Cookie');

        $this->assertNotEmpty($set_cookie_header);
        $this->assertContains($session_cookie_name, $set_cookie_header);
        $this->assertContains('my-session-id', $set_cookie_header);
    }

    /**
     * Test if next middleware in stack is called.
     */
    public function testNextIsCalled()
    {
        /** @var ResponseInterface $response */
        $response = call_user_func(new ApplyAuthenticationMiddleware(new RequestValueContainer('test_transport')), $this->request, $this->response, function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {
            $response = $response->withHeader('X-Test', 'Yes, found!');

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Yes, found!', $response->getHeaderLine('X-Test'));
    }

    /**
     * Test if authentication is applied based on request attribute.
     */
    public function testUserIsAuthentiatedOnExit()
    {
        $user = new AuthenticatedUser(1, 'ilija.studen@activecollab.com', 'Ilija Studen', '123');
        $user_repository = new UserRepository([
            'ilija.studen@activecollab.com' => new AuthenticatedUser(1, 'ilija.studen@activecollab.com', 'Ilija Studen', '123'),
        ]);
        $session_repository = new SessionRepository([new Session('my-session-id', 'ilija.studen@activecollab.com')]);

        $session_cookie_name = 'test-session-cookie';

        $session_adapter = new BrowserSessionAdapter($user_repository, $session_repository, $this->cookies, $session_cookie_name);
        $session = $session_adapter->authenticate($user, []);

        /** @var ServerRequestInterface $request */
        $request = $this->request->withAttribute('test_transport', new AuthorizationTransport($session_adapter, $user, $session, [1, 2, 3]));

        $middleware = new ApplyAuthenticationMiddleware(new RequestValueContainer('test_transport'), true);
        $this->assertTrue($middleware->applyOnExit());

        /** @var ResponseInterface $response */
        $response = call_user_func($middleware, $request, $this->response, function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {
            $this->assertEmpty($response->getHeaderLine('Set-Cookie'));

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $response);

        $set_cookie_header = $response->getHeaderLine('Set-Cookie');

        $this->assertNotEmpty($set_cookie_header);
        $this->assertContains($session_cookie_name, $set_cookie_header);
        $this->assertContains('my-session-id', $set_cookie_header);
    }
}
