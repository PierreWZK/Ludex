<?php

namespace App\Reward;

/**
 * Les récompenses : un palier sur une mesure de la collection, et le cosmétique qu'il débloque.
 * Les mesures sont calculées par RewardEngine.
 */
final class Rewards
{
    public const GAMES = 'games';                 // jeux en collection
    public const TOTAL_HOURS = 'total_hours';     // heures de jeu au total
    public const BEST_HOURS = 'best_hours';       // heures sur un même jeu
    public const ACHIEVEMENTS = 'achievements';   // succès Steam obtenus
    public const PLATINUMS = 'platinums';         // jeux à 100 %
    public const RARE = 'rare';                   // succès obtenus par moins de 1 % des joueurs

    /** @var list<array{code: string, name: string, description: string, cosmetic: string, metric: string, threshold: int}> */
    public const ALL = [
        ['code' => 'premier-pas', 'name' => 'Premier pas', 'description' => 'Un premier jeu dans la collection', 'cosmetic' => 'badge', 'metric' => self::GAMES, 'threshold' => 1],
        ['code' => 'etagere', 'name' => 'Belle étagère', 'description' => '10 jeux dans la collection', 'cosmetic' => 'badge', 'metric' => self::GAMES, 'threshold' => 10],
        ['code' => 'ludotheque', 'name' => 'Ludothèque', 'description' => '100 jeux dans la collection', 'cosmetic' => 'cadre', 'metric' => self::GAMES, 'threshold' => 100],
        ['code' => 'marathon', 'name' => 'Marathonien', 'description' => '100 heures sur un même jeu', 'cosmetic' => 'titre', 'metric' => self::BEST_HOURS, 'threshold' => 100],
        ['code' => 'mille-heures', 'name' => 'Mille heures', 'description' => '1 000 heures de jeu au total', 'cosmetic' => 'cadre', 'metric' => self::TOTAL_HOURS, 'threshold' => 1000],
        ['code' => 'premier-succes', 'name' => 'Premier succès', 'description' => 'Un premier succès Steam', 'cosmetic' => 'badge', 'metric' => self::ACHIEVEMENTS, 'threshold' => 1],
        ['code' => 'cent-succes', 'name' => 'Centurion', 'description' => '100 succès Steam', 'cosmetic' => 'badge', 'metric' => self::ACHIEVEMENTS, 'threshold' => 100],
        ['code' => 'mille-succes', 'name' => 'Collectionneur de succès', 'description' => '1 000 succès Steam', 'cosmetic' => 'cadre', 'metric' => self::ACHIEVEMENTS, 'threshold' => 1000],
        ['code' => 'platine', 'name' => 'Première platine', 'description' => 'Tous les succès d\'un jeu', 'cosmetic' => 'badge', 'metric' => self::PLATINUMS, 'threshold' => 1],
        ['code' => 'cinq-platines', 'name' => 'Cinq platines', 'description' => 'Tous les succès de 5 jeux', 'cosmetic' => 'cadre', 'metric' => self::PLATINUMS, 'threshold' => 5],
        ['code' => 'platineur', 'name' => 'Platineur', 'description' => 'Tous les succès de 25 jeux', 'cosmetic' => 'titre', 'metric' => self::PLATINUMS, 'threshold' => 25],
        ['code' => 'rarissime', 'name' => 'Chasseur de raretés', 'description' => 'Un succès obtenu par moins de 1 % des joueurs', 'cosmetic' => 'titre', 'metric' => self::RARE, 'threshold' => 1],
    ];
}
