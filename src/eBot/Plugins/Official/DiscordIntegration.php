<?php

/**
 * eBot - A bot for match management for CS:GO
 * @license     http://creativecommons.org/licenses/by/3.0/ Creative Commons 3.0
 * @author      Julien Pardons <julien.pardons@esport-tools.net>
 * @version     3.0
 * @date        21/10/2012
 */

namespace eBot\Plugins\Official;

use eBot\Events\Event\ErrorEvent;
use eBot\Events\Event\Kill;
use eBot\Events\Event\MatchEnd;
use eBot\Events\Event\MatchEngaged;
use eBot\Events\Event\MatchStatusUpdate;
use eBot\Events\Event\Pause;
use eBot\Events\Event\RoundRestored;
use eBot\Events\Event\RoundScored;
use eBot\Events\EventDispatcher;
use eBot\Plugins\Plugin;
use eTools\Utils\Logger;
use eBot\Exception\PluginException;

/**
 * Description of DiscordIntegration
 *
 * @author Julien Pardons
 */
class DiscordIntegration implements Plugin
{

    const SCOPE_EBOT = 'ebot';
    const SCOPE_MATCH_STATUS = 'match_status';
    const SCOPE_SCORE = 'score';
    const SCOPE_KILLS = 'kills';
    const SCOPE_ERROR = 'error';

    private $configs = [];

    public function init($config)
    {
        Logger::log("Init DiscordIntegration");

        if (count($config['url']) !== count($config['scopes'])) {
            throw new PluginException("Bad config for discord integration");
        }
        foreach ($config['url'] as $index => $url) {
            $this->configs[] = [
                'url'    => $url,
                'scopes' => explode(',', $config['scopes'][$index]),
            ];
        }

        Logger::log('Loaded ' . count($this->configs) . ' discord webhooks');
    }

    public function onEvent($event)
    {
        switch (get_class($event)) {
            case Pause::class:
                $this->handlePause($event);
                break;
            case MatchEngaged::class:
                $this->handleMatchEngaged($event);
                break;
            case ErrorEvent::class:
                $this->handleError($event);
                break;
            case RoundRestored::class:
                $this->handleRoundRestored($event);
                break;
            case MatchStatusUpdate::class:
                $this->handleMatchStatusUpdate($event);
                break;
            case MatchEnd::class:
                $this->handleMatchEnd($event);
                break;
            case RoundScored::class:
                $this->handleRoundScored($event);
                break;
            case Kill::class:
                $this->handleKill($event);
                break;
        }
    }

    private function handlePause(Pause $event)
    {
        $fields = [];
        if ($event->getTeam()) {
            $fields[] = [
                'name'  => 'Team',
                'value' => $event->getTeamName() . ' (' . $event->getTeam() . ')',
            ];
        }

        $fields[] = [
            'name'  => 'Type',
            'value' => $event->getType(),
        ];

        $fields[] = [
            'name'  => 'Admin',
            'value' => $event->getAdmin() ? 'yes' : 'no',
        ];

        $this->dispatch(self::SCOPE_ERROR, [
            'title'  => '[' . ($event->getPause() ? 'PAUSE' : 'UNPAUSE') . '] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'color'  => 0xef4444,
            'fields' => $fields,
        ]);
    }

    private function handleError(ErrorEvent $event)
    {
        $this->dispatch(self::SCOPE_ERROR, [
            'title'       => '[ERROR] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'description' => $event->getMessage(),
            'color'       => 0xef4444,
        ]);
    }

    private function handleMatchEngaged(MatchEngaged $event)
    {
        $this->dispatch(self::SCOPE_MATCH_STATUS, [
            'title'       => '[MATCH LOADED] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB() . " on " . $event->getMatch()->getServerIp(),
            'description' => 'Status : ' . $event->getMatch()->getStatusText(),
            'color'       => 0x84cc16,
        ]);
    }

    private function handleMatchStatusUpdate(MatchStatusUpdate $event)
    {
        $this->dispatch(self::SCOPE_MATCH_STATUS, [
            'title'       => '[STATUS UPDATE] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'description' => $event->getStatusText(),
            'color'       => 0x84cc16,
        ]);
    }

    private function handleMatchEnd(MatchEnd $event)
    {
        $this->dispatch(self::SCOPE_SCORE, [
            'title'       => '[MATCH END] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'description' => $event->getMatch()->getTeamA() . ' (' . $event->getScore1() . ') VS (' . $event->getScore2() . ') ' . $event->getMatch()->getTeamB(),
            'color'       => 0x84cc16,
        ]);
    }

    private function handleKill(Kill $event)
    {
        $this->dispatch(self::SCOPE_KILLS, [
            'title'       => '[KILL] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'description' => $event->getUserName() . ' killed ' . $event->getKilledUserName() . ' with ' . $event->getWeapon(),
            'color'       => 0x06b6d4,
        ]);
    }

    private function handleRoundScored(RoundScored $event)
    {
        $this->dispatch(self::SCOPE_SCORE, [
            'title'       => '[SCORE UPDATE] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'description' => $event->getTeamA() . ' (' . $event->getScoreA() . ') VS (' . $event->getScoreB() . ') ' . $event->getTeamB(),
            'color'       => 0x84cc16,
        ]);
    }

    private function handleRoundRestored(RoundRestored $event)
    {
        $this->dispatch(self::SCOPE_SCORE, [
            'title'       => '[ROUND RESTORED BY ' . ($event->getAdmin() ? 'ADMIN' : 'PLAYERS') . '] ' . $event->getMatch()->getTeamA() . ' VS ' . $event->getMatch()->getTeamB(),
            'description' => $event->getTeamA() . ' (' . $event->getScoreA() . ') VS (' . $event->getScoreB() . ') ' . $event->getTeamB(),
            'color'       => 0x84cc16,
        ]);
    }

    public function onEventAdded($name)
    {

    }

    public function onEventRemoved($name)
    {

    }

    public function onReload()
    {
        Logger::log("Reloading " . get_class($this));
    }

    public function onStart()
    {
        Logger::log("Starting " . get_class($this));

        $this->dispatch(self::SCOPE_EBOT, [
            'title'       => 'eBot started',
            'description' => 'The Discord integration for eBot has been started with ' . count($this->configs) . ' configurations',
            'color'       => 0xff8500,
        ]);
    }

    public function onEnd()
    {
        Logger::log("Ending " . get_class($this));
    }

    public function getEventList()
    {
        return [
            RoundScored::class,
            ErrorEvent::class,
            MatchEnd::class,
            Kill::class,
            MatchEngaged::class,
            RoundRestored::class,
            MatchStatusUpdate::class,
            Pause::class,
        ];
    }

    private function dispatch($scope, $embed)
    {
        $message = [
            'username'   => 'eBot',
            'avatar_url' => 'https://docs.esport-tools.net/icon-ebot.png',
            'embeds'     => [$embed],
        ];

        foreach ($this->configs as $config) {
            if (in_array($scope, $config['scopes'])) {
                Logger::debug('Sending to ' . $config['url']);
                $this->send($config['url'], $message);
            }
        }
    }

    private function send($url, $embed)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($embed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        print_r($response);
        curl_close($ch);
    }

}
