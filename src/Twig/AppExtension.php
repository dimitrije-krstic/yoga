<?php
declare(strict_types=1);

namespace App\Twig;

use App\Client\RedisClient;
use App\Model\OpenGraphModel;
use Fusonic\OpenGraph\Consumer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const LINK_PATTERN = '#\bhttps?://[^,\s()<>]+(?:\([\w]+\)|([^,[:punct:]\s]|/))#';
    private const REDIS_PREFIX_FORUM_LINKS = 'forum_links_';

    private ContainerInterface $container;
    private string $publicDir;
    private ClientInterface $defaultClient;
    private RedisClient $redisClient;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private RequestStack $requestStack;

    public function __construct(
        ContainerInterface $container,
        string $publicDir,
        ClientInterface $defaultClient,
        RedisClient $redisClient,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        RequestStack $requestStack
    ) {
        $this->container = $container;
        $this->publicDir = $publicDir;
        $this->defaultClient = $defaultClient;
        $this->redisClient = $redisClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource']),
            new TwigFunction('create_open_graph_image', [$this, 'createOpenGraphImage']),
            new TwigFunction('extract_link', [$this, 'extractLink']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('format_links', [$this, 'formatLinks']),
        ];
    }

    public function formatLinks($string):string
    {
        $internalLink = null;
        if ($request = $this->requestStack->getCurrentRequest()) {
            $domain = $request->isSecure() ? 'https://' : 'http://';
            $host = $request->getHost();
            $internalLink = $domain.$host;
        }

        preg_match_all(self::LINK_PATTERN, $string, $match);
        foreach ($match[0] as $url) {
            $replace = '<a href="'.$url.'" rel="external nofollow noopener" target="_blank">'.$url.'</a>';
            if ($internalLink && strpos($string, $internalLink)) {
                $replace = '<a href="'.$url.'">click here</a> to check it out.';
            }

            $string = str_replace($url, $replace, $string);
        }

        return $string;
    }

    public function extractLink($string): string
    {
        preg_match_all(self::LINK_PATTERN, $string, $match);

        return empty($match[0]) ? '' : $match[0][0];
    }

    public function createOpenGraphImage($string): ?OpenGraphModel
    {
        $key = md5($string);
        if ($data = $this->redisClient->getItem(self::REDIS_PREFIX_FORUM_LINKS . $key)) {

            return $data === 'no-og' ? null : $this->serializer->deserialize($data, OpenGraphModel::class, 'json');
        }

        try {
            $response = $this->defaultClient->request(
                'GET',
                $string
            );
        } catch (GuzzleException $e) {
            $this->redisClient->saveItem(self::REDIS_PREFIX_FORUM_LINKS . $key, 'no-og');
            $this->logger->error('ApiClientError-OpenGraph: ' . $e->getMessage());

            return null;
        }

        $consumer = new Consumer();
        $object = $consumer->loadHtml($response->getBody()->getContents());

        if (!$object->url || empty($object->images[0]) || empty($image = $object->images[0]->url)) {
            $this->redisClient->saveItem(self::REDIS_PREFIX_FORUM_LINKS . $key, 'no-og');

            return null;
        }

        $openGraphModel = new OpenGraphModel(
            $object->siteName ?? '',
            $object->title ?? '',
            $object->url,
            $image
        );

        $this->redisClient->saveItem(
            self::REDIS_PREFIX_FORUM_LINKS . $key,
            $this->serializer->serialize($openGraphModel, 'json')
        );

        return $openGraphModel;
    }

    public function getEncoreEntryCssSource(string $entryName): string
    {
        $files = $this->container
            ->get(EntrypointLookupInterface::class)
            ->getCssFiles($entryName);

        $source = '';
        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir.'/'.$file);
        }

        return $source;
    }

    public static function getSubscribedServices()
    {
        return [
            EntrypointLookupInterface::class,
        ];
    }
}
