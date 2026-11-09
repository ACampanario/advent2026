<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use Anthropic\Client;
use App\Middleware\HostHeaderMiddleware;
use App\Model\Table\CustomersTable;
use App\Model\Table\MessagesTable;
use App\Model\Table\OrdersTable;
use App\Service\Ai\AgentService;
use App\Service\Ai\ClaudeGateway;
use App\Service\Ai\CostCalculator;
use App\Service\Ai\MarkdownRenderer;
use App\Service\Ai\SystemPrompt;
use App\Service\Ai\Tool\FindCustomersTool;
use App\Service\Ai\Tool\FindOrdersTool;
use App\Service\Ai\Tool\GetCustomerTool;
use App\Service\Ai\Tool\GetOrderTool;
use App\Service\Ai\Tool\SalesSummaryTool;
use App\Service\Ai\Tool\ToolRegistry;
use App\Service\Ai\TranscriptBuilder;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableContainer;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        // By default, does not allow fallback classes.
        FactoryLocator::add(
            'Table',
            (new TableLocator())->allowFallbackClass(false),
        );
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))

            // Validate Host header to prevent Host Header Injection attacks.
            // In production, ensures App.fullBaseUrl is configured and validates
            // the incoming Host header against it.
            ->add(new HostHeaderMiddleware())

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance.
            // See https://github.com/CakeDC/cakephp-cached-routing
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/5/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware())

            // Cross Site Request Forgery (CSRF) Protection Middleware
            // https://book.cakephp.org/5/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
            ]))

            // Identify the user from the session (demo profile selector, no password).
            ->add(new AuthenticationMiddleware($this))

            // Policies and scopes: the security boundary of the agent tools.
            ->add(new AuthorizationMiddleware($this));

        return $middlewareQueue;
    }

    /**
     * Authentication service: the identity lives in the session only.
     *
     * The demo has no password login; the user picks a profile on /login and
     * the controller stores it as the session identity.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService([
            'unauthenticatedRedirect' => Router::url(['controller' => 'Users', 'action' => 'login', 'plugin' => null]),
            'queryParam' => 'redirect',
        ]);
        $service->loadAuthenticator('Authentication.Session');

        return $service;
    }

    /**
     * Authorization service: Table classes map to *TablePolicy, entities to *Policy.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        return new AuthorizationService(new OrmResolver());
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        // Allow your Tables to be dependency injected
        $container->delegate(new TableContainer());

        // Agent tools, in the fixed order sent to the API (part of the cached prompt prefix).
        $container->addShared(ToolRegistry::class, function () use ($container): ToolRegistry {
            $maxRows = (int)Configure::read('Agent.maxRows', 20);
            $customers = $container->get(CustomersTable::class);
            $orders = $container->get(OrdersTable::class);

            return new ToolRegistry(
                new FindCustomersTool($customers, $maxRows),
                new GetCustomerTool($customers, $maxRows),
                new FindOrdersTool($orders, $maxRows),
                new GetOrderTool($orders, $maxRows),
                new SalesSummaryTool($orders, $maxRows),
            );
        });

        $container->addShared(ClaudeGateway::class, function (): ClaudeGateway {
            return new ClaudeGateway(new Client(apiKey: (string)Configure::read('Anthropic.apiKey')));
        });
        $container->addShared(CostCalculator::class, function (): CostCalculator {
            return new CostCalculator(
                (float)Configure::read('Anthropic.prices.input'),
                (float)Configure::read('Anthropic.prices.output'),
                (float)Configure::read('Anthropic.prices.cacheRead'),
                (float)Configure::read('Anthropic.prices.cacheWrite'),
            );
        });
        $container->addShared(MarkdownRenderer::class);
        $container->addShared(TranscriptBuilder::class)->addArgument(MarkdownRenderer::class);
        $container->addShared(AgentService::class, function () use ($container): AgentService {
            return new AgentService(
                $container->get(ClaudeGateway::class),
                $container->get(ToolRegistry::class),
                new SystemPrompt(),
                $container->get(CostCalculator::class),
                $container->get(MessagesTable::class),
                (string)Configure::read('Anthropic.model'),
                (string)Configure::read('Anthropic.effort'),
                (int)Configure::read('Anthropic.maxTokens'),
                (int)Configure::read('Agent.maxIterations'),
            );
        });
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager
     * @return \Cake\Event\EventManagerInterface
     * @link https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        // $eventManager->on(new SomeCustomListenerClass());

        return $eventManager;
    }
}
