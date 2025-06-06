<?php

namespace ccxt\pro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use \React\Async;
use \React\Promise\PromiseInterface;

class luno extends \ccxt\async\luno {

    public function describe(): mixed {
        return $this->deep_extend(parent::describe(), array(
            'has' => array(
                'ws' => true,
                'watchTicker' => false,
                'watchTickers' => false,
                'watchTrades' => true,
                'watchTradesForSymbols' => false,
                'watchMyTrades' => false,
                'watchOrders' => null, // is in beta
                'watchOrderBook' => true,
                'watchOHLCV' => false,
            ),
            'urls' => array(
                'api' => array(
                    'ws' => 'wss://ws.luno.com/api/1',
                ),
            ),
            'options' => array(
                'sequenceNumbers' => array(),
            ),
            'streaming' => array(
            ),
            'exceptions' => array(
            ),
        ));
    }

    public function watch_trades(string $symbol, ?int $since = null, ?int $limit = null, $params = array ()): PromiseInterface {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * get the list of most recent $trades for a particular $symbol
             *
             * @see https://www.luno.com/en/developers/api#tag/Streaming-API
             *
             * @param {string} $symbol unified $symbol of the $market to fetch $trades for
             * @param {int} [$since] timestamp in ms of the earliest trade to fetch
             * @param {int} [$limit] the maximum amount of    $trades to fetch
             * @param {array} [$params] extra parameters specific to the exchange API endpoint
             * @return {array[]} a list of ~@link https://docs.ccxt.com/#/?id=public-$trades trade structures~
             */
            $this->check_required_credentials();
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $subscriptionHash = '/stream/' . $market['id'];
            $subscription = array( 'symbol' => $symbol );
            $url = $this->urls['api']['ws'] . $subscriptionHash;
            $messageHash = 'trades:' . $symbol;
            $subscribe = array(
                'api_key_id' => $this->apiKey,
                'api_key_secret' => $this->secret,
            );
            $request = $this->deep_extend($subscribe, $params);
            $trades = Async\await($this->watch($url, $messageHash, $request, $subscriptionHash, $subscription));
            if ($this->newUpdates) {
                $limit = $trades->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function handle_trades(Client $client, $message, $subscription) {
        //
        //     {
        //         "sequence" => "110980825",
        //         "trade_updates" => array(),
        //         "create_update" => array(
        //             "order_id" => "BXHSYXAUMH8C2RW",
        //             "type" => "ASK",
        //             "price" => "24081.09000000",
        //             "volume" => "0.07780000"
        //         ),
        //         "delete_update" => null,
        //         "status_update" => null,
        //         "timestamp" => 1660598775360
        //     }
        //
        $rawTrades = $this->safe_value($message, 'trade_updates', array());
        $length = count($rawTrades);
        if ($length === 0) {
            return;
        }
        $symbol = $subscription['symbol'];
        $market = $this->market($symbol);
        $messageHash = 'trades:' . $symbol;
        $stored = $this->safe_value($this->trades, $symbol);
        if ($stored === null) {
            $limit = $this->safe_integer($this->options, 'tradesLimit', 1000);
            $stored = new ArrayCache ($limit);
            $this->trades[$symbol] = $stored;
        }
        for ($i = 0; $i < count($rawTrades); $i++) {
            $rawTrade = $rawTrades[$i];
            $trade = $this->parse_trade($rawTrade, $market);
            $stored->append ($trade);
        }
        $this->trades[$symbol] = $stored;
        $client->resolve ($this->trades[$symbol], $messageHash);
    }

    public function parse_trade($trade, $market = null): array {
        //
        // watchTrades (public)
        //
        //     {
        //       "base" => "69.00000000",
        //       "counter" => "113.6499000000000000",
        //       "maker_order_id" => "BXEEU4S2BWF5WRB",
        //       "taker_order_id" => "BXKNCSF7JDHXY3H",
        //       "order_id" => "BXEEU4S2BWF5WRB"
        //     }
        //
        return $this->safe_trade(array(
            'info' => $trade,
            'id' => null,
            'timestamp' => null,
            'datetime' => null,
            'symbol' => $market['symbol'],
            'order' => null,
            'type' => null,
            'side' => null,
            // takerOrMaker has no meaning for public trades
            'takerOrMaker' => null,
            'price' => null,
            'amount' => $this->safe_string($trade, 'base'),
            'cost' => $this->safe_string($trade, 'counter'),
            'fee' => null,
        ), $market);
    }

    public function watch_order_book(string $symbol, ?int $limit = null, $params = array ()): PromiseInterface {
        return Async\async(function () use ($symbol, $limit, $params) {
            /**
             * watches information on open orders with bid (buy) and ask (sell) prices, volumes and other data
             * @param {string} $symbol unified $symbol of the $market to fetch the order book for
             * @param {int} [$limit] the maximum amount of order book entries to return
             * @param {arrayConstructor} [$params] extra parameters specific to the exchange API endpoint
             * @param {string} [$params->type] accepts l2 or l3 for level 2 or level 3 order book
             * @return {array} A dictionary of ~@link https://docs.ccxt.com/#/?id=order-book-structure order book structures~ indexed by $market symbols
             */
            $this->check_required_credentials();
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $subscriptionHash = '/stream/' . $market['id'];
            $subscription = array( 'symbol' => $symbol );
            $url = $this->urls['api']['ws'] . $subscriptionHash;
            $messageHash = 'orderbook:' . $symbol;
            $subscribe = array(
                'api_key_id' => $this->apiKey,
                'api_key_secret' => $this->secret,
            );
            $request = $this->deep_extend($subscribe, $params);
            $orderbook = Async\await($this->watch($url, $messageHash, $request, $subscriptionHash, $subscription));
            return $orderbook->limit ();
        }) ();
    }

    public function handle_order_book(Client $client, $message, $subscription) {
        //
        //     {
        //         "sequence" => "24352",
        //         "asks" => [array(
        //             "id" => "BXMC2CJ7HNB88U4",
        //             "price" => "1234.00",
        //             "volume" => "0.93"
        //         )],
        //         "bids" => [array(
        //             "id" => "BXMC2CJ7HNB88U5",
        //             "price" => "1201.00",
        //             "volume" => "1.22"
        //         )],
        //         "status" => "ACTIVE",
        //         "timestamp" => 1528884331021
        //     }
        //
        //  update
        //     {
        //         "sequence" => "110980825",
        //         "trade_updates" => array(),
        //         "create_update" => array(
        //             "order_id" => "BXHSYXAUMH8C2RW",
        //             "type" => "ASK",
        //             "price" => "24081.09000000",
        //             "volume" => "0.07780000"
        //         ),
        //         "delete_update" => null,
        //         "status_update" => null,
        //         "timestamp" => 1660598775360
        //     }
        //
        $symbol = $subscription['symbol'];
        $messageHash = 'orderbook:' . $symbol;
        $timestamp = $this->safe_integer($message, 'timestamp');
        if (!(is_array($this->orderbooks) && array_key_exists($symbol, $this->orderbooks))) {
            $this->orderbooks[$symbol] = $this->indexed_order_book(array());
        }
        $asks = $this->safe_value($message, 'asks');
        if ($asks !== null) {
            $snapshot = $this->custom_parse_order_book($message, $symbol, $timestamp, 'bids', 'asks', 'price', 'volume', 'id');
            $this->orderbooks[$symbol] = $this->indexed_order_book($snapshot);
        } else {
            $ob = $this->orderbooks[$symbol];
            $this->handle_delta($ob, $message);
            $ob['timestamp'] = $timestamp;
            $ob['datetime'] = $this->iso8601($timestamp);
        }
        $orderbook = $this->orderbooks[$symbol];
        $nonce = $this->safe_integer($message, 'sequence');
        $orderbook['nonce'] = $nonce;
        $client->resolve ($orderbook, $messageHash);
    }

    public function custom_parse_order_book($orderbook, $symbol, $timestamp = null, $bidsKey = 'bids', int|string $asksKey = 'asks', int|string $priceKey = 'price', int|string $amountKey = 'volume', int|string $countOrIdKey = 2) {
        $bids = $this->parse_bids_asks($this->safe_value($orderbook, $bidsKey, array()), $priceKey, $amountKey, $countOrIdKey);
        $asks = $this->parse_bids_asks($this->safe_value($orderbook, $asksKey, array()), $priceKey, $amountKey, $countOrIdKey);
        return array(
            'symbol' => $symbol,
            'bids' => $this->sort_by($bids, 0, true),
            'asks' => $this->sort_by($asks, 0),
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'nonce' => null,
        );
    }

    public function parse_bids_asks($bidasks, int|string $priceKey = 'price', int|string $amountKey = 'volume', int|string $thirdKey = 2) {
        $bidasks = $this->to_array($bidasks);
        $result = array();
        for ($i = 0; $i < count($bidasks); $i++) {
            $result[] = $this->custom_parse_bid_ask($bidasks[$i], $priceKey, $amountKey, $thirdKey);
        }
        return $result;
    }

    public function custom_parse_bid_ask($bidask, int|string $priceKey = 'price', int|string $amountKey = 'volume', int|string $thirdKey = 2) {
        $price = $this->safe_number($bidask, $priceKey);
        $amount = $this->safe_number($bidask, $amountKey);
        $result = array( $price, $amount );
        if ($thirdKey !== null) {
            $thirdValue = $this->safe_string($bidask, $thirdKey);
            $result[] = $thirdValue;
        }
        return $result;
    }

    public function handle_delta($orderbook, $message) {
        //
        //  create
        //     {
        //         "sequence" => "110980825",
        //         "trade_updates" => array(),
        //         "create_update" => array(
        //             "order_id" => "BXHSYXAUMH8C2RW",
        //             "type" => "ASK",
        //             "price" => "24081.09000000",
        //             "volume" => "0.07780000"
        //         ),
        //         "delete_update" => null,
        //         "status_update" => null,
        //         "timestamp" => 1660598775360
        //     }
        //  delete
        //     {
        //         "sequence" => "110980825",
        //         "trade_updates" => array(),
        //         "create_update" => null,
        //         "delete_update" => array(
        //             "order_id" => "BXMC2CJ7HNB88U4"
        //         ),
        //         "status_update" => null,
        //         "timestamp" => 1660598775360
        //     }
        //  trade
        //     {
        //         "sequence" => "110980825",
        //         "trade_updates" => array(
        //             {
        //                 "base" => "0.1",
        //                 "counter" => "5232.00",
        //                 "maker_order_id" => "BXMC2CJ7HNB88U4",
        //                 "taker_order_id" => "BXMC2CJ7HNB88U5"
        //             }
        //         ),
        //         "create_update" => null,
        //         "delete_update" => null,
        //         "status_update" => null,
        //         "timestamp" => 1660598775360
        //     }
        //
        $createUpdate = $this->safe_value($message, 'create_update');
        $asksOrderSide = $orderbook['asks'];
        $bidsOrderSide = $orderbook['bids'];
        if ($createUpdate !== null) {
            $bidAskArray = $this->custom_parse_bid_ask($createUpdate, 'price', 'volume', 'order_id');
            $type = $this->safe_string($createUpdate, 'type');
            if ($type === 'ASK') {
                $asksOrderSide->storeArray ($bidAskArray);
            } elseif ($type === 'BID') {
                $bidsOrderSide->storeArray ($bidAskArray);
            }
        }
        $deleteUpdate = $this->safe_value($message, 'delete_update');
        if ($deleteUpdate !== null) {
            $orderId = $this->safe_string($deleteUpdate, 'order_id');
            $asksOrderSide->storeArray (array( 0, 0, $orderId ));
            $bidsOrderSide->storeArray (array( 0, 0, $orderId ));
        }
    }

    public function handle_message(Client $client, $message) {
        if ($message === '') {
            return;
        }
        $subscriptions = is_array($client->subscriptions) ? array_values($client->subscriptions) : array();
        $handlers = array( array($this, 'handle_order_book'), array($this, 'handle_trades'));
        for ($j = 0; $j < count($handlers); $j++) {
            $handler = $handlers[$j];
            $handler($client, $message, $subscriptions[0]);
        }
    }
}
