<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FiveBridge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'FiveBridge {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public $server;

    protected $player = [];
    protected $observer = [];

    protected $place = ['player_1', 'player_2', 'player_3', 'player_4', 'player_5'];

    protected $nextBidder = 'player_1';

    protected $kingSuits = '';

    protected $kingPlayer = '';

    protected $jokerPlayer = '';

    protected $kingTarget = 0;

    protected $peopleTarget = 0;

    protected $bidding = [
        'player_5' => '',
        'player_4' => '',
        'player_3' => '',
        'player_2' => '',
        'player_1' => '',
    ];

    protected $player_1_order = [
        'player_5',
        'player_4',
        'player_3',
        'player_2',
    ];

    protected $player_2_order = [
        'player_1',
        'player_5',
        'player_4',
        'player_3',
    ];

    protected $player_3_order = [
        'player_2',
        'player_1',
        'player_5',
        'player_4',
    ];

    protected $player_4_order = [
        'player_3',
        'player_2',
        'player_1',
        'player_5',
    ];

    protected $player_5_order = [
        'player_4',
        'player_3',
        'player_2',
        'player_1',
    ];

    protected $hands = [
        'player_1' => [],
        'player_2' => [],
        'player_3' => [],
        'player_4' => [],
        'player_5' => []
    ];

    protected $biddingCount = 0;

    protected $riverCards = [];

    protected $round = [];

    protected $point = [
        'player_1' => 0,
        'player_2' => 0,
        'player_3' => 0,
        'player_4' => 0,
        'player_5' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'close':
                break;
            case 'start':
                $this->start();
                break;
            default:
                break;
        }
    }

    private function start()
    {
        $server = new \Swoole\WebSocket\Server("0.0.0.0", 9502);

        $this->server = $server;

        $server->on('open', function ($server, $request) {
            $this->info("WebSocket connect open! fd: " . $request->fd);
        });

        $server->on('request', function ($request, $response) {
            // 设置允许跨域访问的响应头
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
        });

        $server->on('message', function ($server, $frame) {
            $this->info("client message: " . $frame->data);
            $data = json_decode($frame->data, true);
            switch ($data['action']) {
                case 'character':
                    $this->setCharacter($data, $frame->fd);
                    $this->setAllPlayer();
                    break;
                case 'deal':
                    $this->deal();
                    $this->callNextBidder(true);
                    break;
                case 'bidding':
                    $this->bidding($data);
                    $this->callNextBidder();
                    break;
                case 'biddingCheck':
                    $this->biddingCheck($data);
                    break;
                case 'kingShuffle':
                    $this->kingShuffle($data);
                    break;
                case 'confirmChange':
                    $this->confirmChange($data);
                    break;
                case 'play':
                    $this->play($data);
                    break;
            }
        });

        $server->on('close', function ($ws, $fd) {
            $this->info($fd . " client is close\n");
            if (array_key_exists($fd, $this->player)) {
                foreach ($this->server->connections as $fd) {
                    if (!$this->server->isEstablished($fd)) {
                        continue;
                    }
                    $item = [
                        'action' => 'playerClose',
                    ];
                    $this->server->push($fd, json_encode($item));
                }
//                $this->setAllPlayer();
            }
        });

        $server->start();
    }

    public function setCharacter($data, $fd)
    {
        if ($data['character'] === 'player' && count($this->player) < 5) {
            $item = [
                'action' => 'character',
                'place' => array_shift($this->place),
                'name' => $data['name'],
            ];
            $this->player[$fd] = $item;
            $this->server->push($fd, json_encode($item));
            if (count($this->player) == 5) {
                foreach ($this->player as $fd => $data) {
                    if (!$this->server->isEstablished($fd)) {
                        continue;
                    }
                    $item = [
                        'action' => 'ready',
                    ];
                    $this->server->push($fd, json_encode($item));
                }
            }
            return;
        }
        $item = [
            'action' => 'character',
            'place' => 'observer',
            'name' => $data['name'],
        ];
        $this->observer[$fd] = $item;
        $this->server->push($fd, json_encode($item));

    }

    public function setAllPlayer()
    {
        foreach ($this->server->connections as $fd) {
            if (!$this->server->isEstablished($fd)) {
                continue;
            }
            $item = [
                'action' => 'allCharacter',
                'data' => $this->player,
            ];
            $this->server->push($fd, json_encode($item));
        }
    }

    public function deal()
    {
        $cards = collect(config('poker'))->shuffle();

        $this->riverCards = $cards->pop(3)->toArray();
        for ($i = 1; $i <= 5; $i++) {

            $sortingOrder = [
                "spade", "heart", "diamond", "club", "joker"
            ];

            $rankOrder = [
                "2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K", "A", "black"
            ];

            $sortedArray = collect($cards->pop(10))->sort(function ($a, $b) use ($sortingOrder, $rankOrder) {
                $aParts = explode("_", $a);
                $bParts = explode("_", $b);

                $aSuit = $aParts[0];
                $bSuit = $bParts[0];

                $aRank = $aParts[1];
                $bRank = $bParts[1];

                $aSuitOrder = array_search($aSuit, $sortingOrder);
                $bSuitOrder = array_search($bSuit, $sortingOrder);

                $aRankOrder = array_search($aRank, $rankOrder);
                $bRankOrder = array_search($bRank, $rankOrder);

                if ($aSuitOrder === $bSuitOrder) {
                    return $aRankOrder - $bRankOrder;
                }

                return $aSuitOrder - $bSuitOrder;
            })->values()->all();
            foreach ($sortedArray as $card) {
                if ($card == 'joker_black') {
                    $this->jokerPlayer = "player_$i";
                }
            }
            $this->hands["player_{$i}"] = $sortedArray;
        }
        foreach ($this->player as $fd => $data) {
            if (!$this->server->isEstablished($fd)) {
                continue;
            }
            $item = [
                'action' => 'hands',
                'hands' => $this->hands[$data['place']]
            ];
            $this->server->push($fd, json_encode($item));
        }
        foreach ($this->observer as $fd => $data) {
            if (!$this->server->isEstablished($fd)) {
                continue;
            }
            $item = [
                'action' => 'hands',
                'hands' => $this->hands['player_1']
            ];
            $this->server->push($fd, json_encode($item));
            $item = [
                'action' => 'rivers',
                'rivers' => $this->riverCards
            ];
            $this->server->push($fd, json_encode($item));
        }
    }

    public function bidding($data)
    {
        $this->bidding[$data['place']] = $data['card'];
        $pass = 0;
        if ($data['card'] !== 'pass') {
            $this->biddingCount++;
        } else {
            $pass++;
        }
        $this->sendBiddingMessage($data);

        $order = $this->{$data['place'] . '_order'};
        $this->info(json_encode($order));
        $nextBidder = '';
        foreach ($order as $player) {
            if ($this->bidding[$player] == 'pass') {
                $pass++;
            }
            if ($nextBidder == '' && $this->bidding[$player] != 'pass') {
                $nextBidder = $player;
            }
        }
        if ($pass == 4) {
            if ($this->bidding[$nextBidder] == 'club_1' && $this->biddingCount == 1) {
                $data = [
                    'action' => 'biddingCheck',
                    'place' => $nextBidder
                ];
                foreach ($this->player as $fd => $item) {
                    if (!$this->server->isEstablished($fd)) {
                        continue;
                    }
                    if ($item['place'] == $nextBidder) {
                        $this->server->push($fd, json_encode($data));
                    }
                }
                $this->nextBidder = $nextBidder;
                $this->callNextBidder();
            } else {
                $data = [
                    'place' => $nextBidder,
                    'card' => $this->bidding[$nextBidder]
                ];
                $this->biddingCheck($data);
            }
        } else {
            $this->nextBidder = $nextBidder;
            $this->callNextBidder();
        }
    }

    public function callNextBidder($first = false)
    {
        foreach ($this->player as $fd => $item) {
            if (!$this->server->isEstablished($fd)) {
                continue;
            }
            $item = [
                'action' => 'bidder',
                'place' => $this->nextBidder,
                'first' => $first
            ];

            $this->server->push($fd, json_encode($item));
        }
    }

    public function biddingCheck($data)
    {
        $this->bidding[$data['place']] = $data['card'];
        $place = $data['place'];
        $data = [
            'action' => 'pending',
            'place' => $data['place'],
            'result' => $this->bidding[$data['place']],
        ];
        $this->kingSuits = explode('_', $this->bidding[$data['place']])[0];
        $this->kingTarget = (int)explode('_', $this->bidding[$data['place']])[1] + 5;
        $this->info($this->kingTarget);
        $this->peopleTarget = 10 - $this->kingTarget;
        $this->info($this->peopleTarget);

        $this->kingPlayer = $this->nextBidder;
        foreach ($this->player as $fd => $item) {
            if (!$this->server->isEstablished($fd)) {
                continue;
            }
            $this->server->push($fd, json_encode($data));
        }

        $data = [
            'action' => 'rivers',
            'rivers' => $this->riverCards
        ];

        foreach ($this->player as $fd => $item) {
            $this->info(json_encode($item));
            $this->info($place);

            if ($item['place'] == $place) {
                $this->server->push($fd, json_encode($data));
            }
        }
    }

    public function sendBiddingMessage($data)
    {
        foreach ($this->player as $fd => $item) {
            if (!$this->server->isEstablished($fd)) {
                continue;
            }
            $item = [
                'action' => 'bidding',
                'place' => $data['place'],
                'card' => $data['card']
            ];

            $this->server->push($fd, json_encode($item));
        }
    }

    public function kingShuffle($data)
    {
        $hands = $this->hands[$data['place']];
        $riverCard = $this->riverCards;
        $this->riverCards = [];

        $newHands = array_merge($hands, $riverCard);

        $sortingOrder = [
            "spade", "heart", "diamond", "club", "joker"
        ];

        $rankOrder = [
            "2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K", "A", "black"
        ];
        $sortedArray = collect($newHands)->sort(function ($a, $b) use ($sortingOrder, $rankOrder) {
            $aParts = explode("_", $a);
            $bParts = explode("_", $b);

            $aSuit = $aParts[0];
            $bSuit = $bParts[0];

            $aRank = $aParts[1];
            $bRank = $bParts[1];

            $aSuitOrder = array_search($aSuit, $sortingOrder);
            $bSuitOrder = array_search($bSuit, $sortingOrder);

            $aRankOrder = array_search($aRank, $rankOrder);
            $bRankOrder = array_search($bRank, $rankOrder);

            if ($aSuitOrder === $bSuitOrder) {
                return $aRankOrder - $bRankOrder;
            }

            return $aSuitOrder - $bSuitOrder;
        })->values()->all();

        $this->hands[$data['place']] = $sortedArray;
        if ($this->jokerPlayer == '') {
            $this->jokerPlayer = $this->kingPlayer;
        }
        foreach ($this->player as $fd => $item) {
            if ($item['place'] == $data['place']) {
                $this->server->push($fd, json_encode([
                    'action' => 'kingShuffle',
                    'hands' => $sortedArray
                ]));
            }
        }
    }

    public function confirmChange($data)
    {
        $this->riverCards = $data['card'];
        $this->info(json_encode($this->hands[$data['place']]));
        $this->info(json_encode($this->riverCards));

        $this->hands[$data['place']] = array_values(array_diff($this->hands[$data['place']], $this->riverCards));
        $this->info(json_encode($this->hands[$data['place']]));

        foreach ($this->player as $fd => $item) {
            if ($item['place'] == $data['place']) {
                $this->server->push($fd, json_encode([
                    'action' => 'confirmShuffle',
                    'hands' => $this->hands[$data['place']]
                ]));
            }
        }
        foreach ($this->player as $fd => $item) {
            $this->server->push($fd, json_encode([
                'action' => 'start',
            ]));
        }

        if ($data['place'] != 'player_5') {
            $place = 'player_' . ((int)explode('_', $data['place'])[1] + 1);
        } else {
            $place = 'player_1';
        }
        $this->playLoading($place);
    }

    public function playLoading($place): void
    {
        foreach ($this->player as $fd => $item) {
            $this->server->push($fd, json_encode([
                'action' => 'playLoading',
                'place' => $place
            ]));
        }
    }

    public function play($data)
    {
        foreach ($this->player as $fd => $item) {
            $this->server->push($fd, json_encode([
                'action' => 'play',
                'place' => $data['place'],
                'card' => $data['card']
            ]));
        }
        $this->round[$data['round']][$data['place']] = $data['card'];
        if (sizeof($this->round[$data['round']]) == 5) {
            $this->checkWin($data['round']);
        } else {
            if ($data['place'] != 'player_5') {
                $place = 'player_' . ((int)explode('_', $data['place'])[1] + 1);
            } else {
                $place = 'player_1';
            }
            $this->playLoading($place);
        }
    }

    public function winRound($place)
    {
        foreach ($this->player as $fd => $item) {
            $this->server->push($fd, json_encode([
                'action' => 'winRound',
                'place' => $place
            ]));
        }
    }

    public function customSort($a, $b, $suits)
    {
        if ($a === 'joker_black' || $b === 'joker_black') {
            return $a === 'joker_black' ? -1 : 1;
        }

        $rankOrder = ['A', 'K', 'Q', 'J', '10', '9', '8', '7', '6', '5', '4', '3', '2'];
        $suitA = explode('_', $a)[0];
        $rankA = explode('_', $a)[1];

        $suitB = explode('_', $b)[0];
        $rankB = explode('_', $b)[1];

        if ($suitA === $suits && $suitB === $suits) {
            return array_search($rankA, $rankOrder) - array_search($rankB, $rankOrder);
        } elseif ($suitA === $suits) {
            return -1;
        } elseif ($suitB === $suits) {
            return 1;
        }
    }

    public function checkWin($round)
    {
        $cards = [];
        $customSuits = '';
        $kingSuit = false;
        $suits = $this->kingSuits;
        foreach ($this->round[$round] as $place => $card) {
            if ($customSuits == '') {
                $customSuits = explode('_', $card)[0];
            }
            if (explode('_', $card)[0] == 'joker') {
                $this->jokerPlayer = $place;
            }
            if (explode('_', $card)[0] == $this->kingSuits) {
                $kingSuit = true;
            }
            $cards[] = $card;
        }
        if (!$kingSuit) {
            $suits = $customSuits;
        }

        usort($cards, function ($a, $b) use ($suits) {
            return $this->customSort($a, $b, $suits);
        });

        $place = array_keys($this->round[$round], $cards[0])[0];
        $this->info($place);
        $this->info('--------');
        $this->point[$place] = $this->point[$place] + 1;
        $this->winRound($place);
        $peoplePoint = 0;
        $kingPoint = 0;
        foreach ($this->point as $place => $point) {
            if ($this->kingPlayer == $this->jokerPlayer && $place == $this->kingPlayer) {
                $kingPoint = $kingPoint + $point;
                continue;
            }
            if ($this->kingPlayer != $this->jokerPlayer) {
                if ($place == $this->kingPlayer || $place == $this->jokerPlayer) {
                    $kingPoint += $point;
                    continue;
                }
            }

            $peoplePoint = $peoplePoint + $point;
        }

        $this->info('kingPoint:' . $kingPoint);
        $this->info('this->kingTarget:' . $this->kingTarget);

        if ($kingPoint == $this->kingTarget) {
            $minValue = min($this->point);
            $keysWithMinValue = array_keys($this->point, $minValue);
            $randomPlayer = $keysWithMinValue[array_rand($keysWithMinValue)];
            $num = (int)explode('_', $randomPlayer)[1];
            if ($num === 1) {
                $num = 5;
            } else {
                $num--;
            }

            $this->nextBidder = 'player_' . $num;
            foreach ($this->player as $fd => $item) {
                if (!$this->server->isEstablished($fd)) {
                    continue;
                }
                $result = 'lose';
                if ($item['place'] == $this->jokerPlayer || $item['place'] == $this->kingPlayer) {
                    $result = 'win';
                }
                $item = [
                    'action' => 'final',
                    'result' => $result
                ];

                $this->server->push($fd, json_encode($item));
            }
            $this->clear();
            return;
        }
        $this->info('peoplePoint:' . $peoplePoint);
        $this->info('this->peopleTarget:' . $this->peopleTarget);

        if ($peoplePoint == $this->peopleTarget) {
            $this->info('people win');
            $num = (int)explode('_', $this->kingPlayer)[1];
            if ($num === 1) {
                $num = 5;
            } else {
                $num--;
            }
            $this->nextBidder = 'player_' . $num;
            $this->info(  $this->nextBidder );
            foreach ($this->player as $fd => $item) {
                if (!$this->server->isEstablished($fd)) {
                    continue;
                }
                $result = 'lose';
                if ($item['place'] != $this->jokerPlayer && $item['place'] != $this->kingPlayer) {
                    $result = 'win';
                }
                $item = [
                    'action' => 'final',
                    'result' => $result
                ];

                $this->server->push($fd, json_encode($item));
            }
            $this->clear();
            return;
        }

        $this->playLoading($place);
    }

    public function clear()
    {
        $this->hands = [
            'player_1' => [],
            'player_2' => [],
            'player_3' => [],
            'player_4' => [],
            'player_5' => []
        ];

        $this->biddingCount = 0;

        $this->riverCards = [];

        $this->round = [];

        $this->point = [
            'player_1' => 0,
            'player_2' => 0,
            'player_3' => 0,
            'player_4' => 0,
            'player_5' => 0,
        ];
//        $this->nextBidder = 'player_1';

        $this->kingSuits = '';

        $this->kingPlayer = '';

        $this->jokerPlayer = '';

        $this->kingTarget = 0;

        $this->peopleTarget = 0;

        $this->bidding = [
            'player_5' => '',
            'player_4' => '',
            'player_3' => '',
            'player_2' => '',
            'player_1' => '',
        ];
    }
}
