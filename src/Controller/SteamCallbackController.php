<?php

namespace App\Controller;

use App\Entity\User;
use App\Steam\SteamClient;
use App\Steam\SteamOpenId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Le retour de « Se connecter via Steam » : vérifie la preuve auprès de Steam, puis lie le compte */
final class SteamCallbackController extends AbstractController
{
    #[Route('/steam/callback', name: 'steam_callback', methods: ['GET'])]
    public function __invoke(Request $request, SteamOpenId $openId, SteamClient $steam, EntityManagerInterface $em): Response
    {
        $userId = $openId->readState((string) $request->query->get('state'));
        $user = $userId ? $em->find(User::class, $userId) : null;
        if (!$user) {
            return $this->result('expire', Response::HTTP_BAD_REQUEST);
        }

        $steamId = $openId->verify($this->openIdParams($request), $this->generateUrl('steam_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        if (!$steamId) {
            return $this->result('refuse', Response::HTTP_BAD_REQUEST);
        }

        $other = $em->getRepository(User::class)->findOneBy(['steamId' => $steamId]);
        if ($other && $other !== $user) {
            return $this->result('deja_lie', Response::HTTP_CONFLICT);
        }

        $user->setSteamId($steamId);
        if ($steam->isConfigured() && ($profile = $steam->playerSummary($steamId))) {
            $user->setSteamPersona($profile['personaname'] ?? null)->setSteamAvatar($profile['avatarfull'] ?? null);
        }
        $em->flush();

        return $this->result('lie', Response::HTTP_OK, $user);
    }

    /**
     * Les paramètres openid.* tels qu'envoyés par Steam. PHP remplace les points des noms par des tirets bas
     * dans $_GET : on relit donc la chaîne de requête brute.
     *
     * @return array<string, string>
     */
    private function openIdParams(Request $request): array
    {
        $params = [];
        foreach (explode('&', (string) $request->server->get('QUERY_STRING')) as $pair) {
            [$key, $value] = array_map('urldecode', explode('=', $pair, 2) + [1 => '']);
            if (str_starts_with($key, 'openid.')) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function result(string $outcome, int $status, ?User $user = null): Response
    {
        return $this->render('steam.html.twig', ['outcome' => $outcome, 'user' => $user], new Response(status: $status));
    }
}
