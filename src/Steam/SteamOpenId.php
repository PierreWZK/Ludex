<?php

namespace App\Steam;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * « Se connecter via Steam » (OpenID 2.0) : Steam prouve le SteamID du joueur, sans partager d'autre donnée.
 *
 * Le jeton d'état (state) relie le retour de Steam au compte Ludex qui a demandé la liaison : il est signé
 * avec APP_SECRET et expire au bout de 10 minutes.
 */
final readonly class SteamOpenId
{
    public const ENDPOINT = 'https://steamcommunity.com/openid/login';
    private const STATE_TTL = 600;

    public function __construct(
        private HttpClientInterface $http,
        #[Autowire(param: 'kernel.secret')]
        private string $secret,
    ) {
    }

    /** L'adresse où envoyer le joueur ; Steam le renverra sur $returnTo avec la preuve de son identité */
    public function loginUrl(string $returnTo): string
    {
        $parts = parse_url($returnTo);

        return self::ENDPOINT.'?'.http_build_query([
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ]);
    }

    /**
     * Vérifie le retour de Steam auprès de Steam lui-même (check_authentication) et renvoie le SteamID64, ou null.
     *
     * @param array<string, string> $params les paramètres openid.* reçus (clés avec des points, pas des tirets bas)
     */
    public function verify(array $params, string $expectedReturnTo): ?string
    {
        if (($params['openid.mode'] ?? null) !== 'id_res'
            || ($params['openid.op_endpoint'] ?? null) !== self::ENDPOINT
            || !str_starts_with($params['openid.return_to'] ?? '', $expectedReturnTo)
            || !preg_match('#^https://steamcommunity\.com/openid/id/(\d{17})$#', $params['openid.claimed_id'] ?? '', $m)) {
            return null;
        }

        $body = $this->http->request('POST', self::ENDPOINT, [
            'body' => ['openid.mode' => 'check_authentication'] + $params,
            'timeout' => 15,
        ])->getContent();

        return str_contains($body, 'is_valid:true') ? $m[1] : null;
    }

    public function createState(int $userId): string
    {
        $payload = $userId.'.'.(time() + self::STATE_TTL);

        return $payload.'.'.hash_hmac('sha256', $payload, $this->secret);
    }

    /** L'identifiant du compte Ludex si le jeton est intact et pas expiré */
    public function readState(string $state): ?int
    {
        if (!preg_match('/^(\d+)\.(\d+)\.([a-f0-9]{64})$/', $state, $m)
            || !hash_equals(hash_hmac('sha256', $m[1].'.'.$m[2], $this->secret), $m[3])
            || (int) $m[2] < time()) {
            return null;
        }

        return (int) $m[1];
    }
}
