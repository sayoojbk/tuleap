<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Git\Events\GetExternalUsedServiceEvent;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabHTTPClientFactory;
use Tuleap\Gitlab\API\Tag\GitlabTagRetriever;
use Tuleap\Gitlab\EventsHandlers\ReferenceAdministrationWarningsCollectorEventHandler;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\GitlabCrossReferenceOrganizer;
use Tuleap\Gitlab\Reference\GitlabReferenceBuilder;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTagFactory;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryWebhookController;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenDao;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\BotCommentReferencePresenterBuilder;
use Tuleap\Gitlab\Repository\Webhook\Bot\CommentSender;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\InvalidCredentialsNotifier;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\CrossReferenceFromMergeRequestCreator;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestBotCommenter;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookAuthorDataRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PostMergeRequestWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\PreviouslySavedReferencesRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\TuleapReferencesFromMergeRequestDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitBotCommenter;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretRetriever;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookActionProcessor;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagPushWebhookDataBuilder;
use Tuleap\Gitlab\Repository\Webhook\WebhookActions;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataExtractor;
use Tuleap\Gitlab\Repository\Webhook\WebhookRepositoryRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Gitlab\REST\ResourcesInjector;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\Admin\Reference\Browse\ExternalSystemReferencePresenter;
use Tuleap\Project\Admin\Reference\Browse\ExternalSystemReferencePresentersCollector;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class gitlabPlugin extends Plugin
{
    public const SERVICE_NAME   = "gitlab";
    public const LOG_IDENTIFIER = "gitlab_syslog";

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-gitlab', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Gitlab\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(GetExternalUsedServiceEvent::NAME);

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(GetReferenceEvent::NAME);

        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::GET_REFERENCE_ADMIN_CAPABILITIES);
        $this->addHook(NatureCollection::NAME);
        $this->addHook(ReferenceAdministrationWarningsCollectorEvent::NAME);
        $this->addHook(CrossReferenceByNatureOrganizer::NAME);

        $this->addHook(ExternalSystemReferencePresentersCollector::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['git'];
    }

    public function getExternalUsedServiceEvent(GetExternalUsedServiceEvent $event): void
    {
        $project        = $event->getProject();
        $is_gitlab_used = $this->isAllowed((int) $project->getGroupId());

        if (! $is_gitlab_used) {
            return;
        }

        $event->addUsedServiceName(self::SERVICE_NAME);
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectGitlabResource($params['resources'], $params['project']);
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/plugins/gitlab', function (FastRoute\RouteCollector $r) {
            $r->post('/repository/webhook', $this->getRouteHandler('routePostGitlabRepositoryWebhook'));
        });
    }

    public function routePostGitlabRepositoryWebhook(): GitlabRepositoryWebhookController
    {
        $logger            = BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
        $reference_manager = ReferenceManager::instance();

        $request_factory       = HTTPFactoryBuilder::requestFactory();
        $stream_factory        = HTTPFactoryBuilder::streamFactory();
        $gitlab_client_factory = new GitlabHTTPClientFactory(HttpClientFactory::createClient());
        $gitlab_api_client     = new ClientWrapper($request_factory, $stream_factory, $gitlab_client_factory);

        $tuleap_reference_retriever = new TuleapReferenceRetriever(
            EventManager::instance(),
            $reference_manager
        );

        $merge_request_reference_dao = new MergeRequestTuleapReferenceDao();

        $references_from_merge_request_data_extractor = new TuleapReferencesFromMergeRequestDataExtractor(
            new WebhookTuleapReferencesParser(),
        );

        $gitlab_repository_project_retriever = new GitlabRepositoryProjectRetriever(
            new GitlabRepositoryProjectDao(),
            ProjectManager::instance()
        );

        $comment_sender = new CommentSender(
            $gitlab_api_client,
            new InvalidCredentialsNotifier(
                $gitlab_repository_project_retriever,
                new MailBuilder(
                    TemplateRendererFactory::build(),
                    new MailFilter(
                        UserManager::instance(),
                        new ProjectAccessChecker(
                            new RestrictedUserCanAccessProjectVerifier(),
                            EventManager::instance()
                        ),
                        new MailLogger()
                    ),
                ),
                new InstanceBaseURLBuilder(),
                new GitlabBotApiTokenDao(),
                $logger,
            ),
        );

        return new GitlabRepositoryWebhookController(
            new WebhookDataExtractor(
                new PostPushWebhookDataBuilder(
                    new PostPushCommitWebhookDataExtractor(
                        $logger
                    )
                ),
                new PostMergeRequestWebhookDataBuilder($logger),
                new TagPushWebhookDataBuilder(),
                $logger
            ),
            new WebhookRepositoryRetriever(
                $this->getGitlabRepositoryFactory()
            ),
            new SecretChecker(
                new SecretRetriever(
                    new WebhookDao(),
                    new KeyFactory()
                )
            ),
            new WebhookActions(
                new GitlabRepositoryDao(),
                new PostPushWebhookActionProcessor(
                    new WebhookTuleapReferencesParser(),
                    $gitlab_repository_project_retriever,
                    new CommitTuleapReferenceDao(),
                    $reference_manager,
                    $tuleap_reference_retriever,
                    $logger,
                    new PostPushCommitBotCommenter(
                        $comment_sender,
                        new CredentialsRetriever(new GitlabBotApiTokenRetriever(new GitlabBotApiTokenDao(), new KeyFactory())),
                        $logger,
                        new BotCommentReferencePresenterBuilder(new InstanceBaseURLBuilder()),
                        TemplateRendererFactory::build()
                    )
                ),
                new PostMergeRequestWebhookActionProcessor(
                    $merge_request_reference_dao,
                    $gitlab_repository_project_retriever,
                    $logger,
                    new PostMergeRequestBotCommenter(
                        $comment_sender,
                        new CredentialsRetriever(new GitlabBotApiTokenRetriever(new GitlabBotApiTokenDao(), new KeyFactory())),
                        $logger,
                        new BotCommentReferencePresenterBuilder(new InstanceBaseURLBuilder()),
                        TemplateRendererFactory::build()
                    ),
                    new PreviouslySavedReferencesRetriever(
                        $references_from_merge_request_data_extractor,
                        $tuleap_reference_retriever,
                        $merge_request_reference_dao,
                    ),
                    new CrossReferenceFromMergeRequestCreator(
                        $references_from_merge_request_data_extractor,
                        $tuleap_reference_retriever,
                        ReferenceManager::instance(),
                        $logger,
                    ),
                    new PostMergeRequestWebhookAuthorDataRetriever(
                        $gitlab_api_client,
                        new CredentialsRetriever(new GitlabBotApiTokenRetriever(new GitlabBotApiTokenDao(), new KeyFactory()))
                    ),
                    new GitlabMergeRequestReferenceRetriever(new MergeRequestTuleapReferenceDao())
                ),
                new TagPushWebhookActionProcessor(
                    new CredentialsRetriever(new GitlabBotApiTokenRetriever(new GitlabBotApiTokenDao(), new KeyFactory())),
                    new GitlabTagRetriever(
                        $gitlab_api_client
                    ),
                    new WebhookTuleapReferencesParser(),
                    $tuleap_reference_retriever,
                    $gitlab_repository_project_retriever,
                    ReferenceManager::instance(),
                    new TagInfoDao(),
                    $logger,
                ),
                $logger,
            ),
            $logger,
            HTTPFactoryBuilder::responseFactory(),
            new SapiEmitter(),
            new \Tuleap\Http\Server\ServiceInstrumentationMiddleware(self::SERVICE_NAME)
        );
    }

    public function getReference(GetReferenceEvent $event): void
    {
        if (
            $event->getKeyword() === GitlabCommitReference::REFERENCE_NAME ||
            $event->getKeyword() === GitlabMergeRequestReference::REFERENCE_NAME ||
            $event->getKeyword() === GitlabTagReference::REFERENCE_NAME
        ) {
            $builder = new GitlabReferenceBuilder(
                new \Tuleap\Gitlab\Reference\ReferenceDao(),
                $this->getGitlabRepositoryFactory()
            );

            $reference = $builder->buildGitlabReference(
                $event->getProject(),
                $event->getKeyword(),
                $event->getValue()
            );

            if ($reference !== null) {
                $event->setReference($reference);
            }
        }
    }

    public function get_plugins_available_keywords_references(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['keywords'][] = GitlabCommitReference::REFERENCE_NAME;
        $params['keywords'][] = GitlabMergeRequestReference::REFERENCE_NAME;
        $params['keywords'][] = GitlabTagReference::REFERENCE_NAME;
    }

    /** @see \Event::GET_REFERENCE_ADMIN_CAPABILITIES */
    public function get_reference_admin_capabilities(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $reference = $params['reference'];
        \assert($reference instanceof Reference);

        if (
            $reference->getNature() === GitlabCommitReference::NATURE_NAME ||
            $reference->getNature() === GitlabMergeRequestReference::NATURE_NAME ||
            $reference->getNature() === GitlabTagReference::NATURE_NAME
        ) {
            $params['can_be_deleted'] = false;
            $params['can_be_edited']  = false;
        }
    }

    private function getGitlabRepositoryFactory(): GitlabRepositoryFactory
    {
        return new GitlabRepositoryFactory(
            new GitlabRepositoryDao()
        );
    }

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            GitlabCommitReference::NATURE_NAME,
            new Nature(
                GitlabCommitReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab commit'),
                false
            )
        );

        $natures->addNature(
            GitlabMergeRequestReference::NATURE_NAME,
            new Nature(
                GitlabMergeRequestReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab merge request'),
                false
            )
        );

        $natures->addNature(
            GitlabTagReference::NATURE_NAME,
            new Nature(
                GitlabTagReference::REFERENCE_NAME,
                'fab fa-gitlab',
                dgettext('tuleap-gitlab', 'GitLab Tag'),
                false
            )
        );
    }

    public function referenceAdministrationWarningsCollectorEvent(ReferenceAdministrationWarningsCollectorEvent $event): void
    {
        (new ReferenceAdministrationWarningsCollectorEventHandler())->handle($event);
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $organizer): void
    {
        $gitlab_repository_dao = new GitlabRepositoryDao();
        $gitlab_organizer      = new GitlabCrossReferenceOrganizer(
            new GitlabRepositoryFactory($gitlab_repository_dao),
            new GitlabCommitFactory(new CommitTuleapReferenceDao()),
            new GitlabCommitCrossReferenceEnhancer(
                \UserManager::instance(),
                \UserHelper::instance(),
                new TlpRelativeDatePresenterBuilder()
            ),
            new GitlabMergeRequestReferenceRetriever(new MergeRequestTuleapReferenceDao()),
            new GitlabTagFactory(
                new TagInfoDao()
            ),
            ProjectManager::instance(),
            new TlpRelativeDatePresenterBuilder(),
            UserManager::instance(),
            UserHelper::instance()
        );
        $gitlab_organizer->organizeGitLabReferences($organizer);
    }

    public function externalSystemReferencePresentersCollector(ExternalSystemReferencePresentersCollector $collector): void
    {
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabCommitReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab commit'),
                dgettext('tuleap-gitlab', 'GitLab commit'),
            )
        );
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabMergeRequestReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab merge request'),
                dgettext('tuleap-gitlab', 'GitLab merge request'),
            )
        );
        $collector->add(
            new ExternalSystemReferencePresenter(
                GitlabTagReference::REFERENCE_NAME,
                dgettext('tuleap-gitlab', 'Reference to a GitLab tag'),
                dgettext('tuleap-gitlab', 'GitLab tag'),
            )
        );
    }
}
