<?php

namespace App\Libraries;

use Goutte\Client;

class VoipCrawler
{

	public static function getCreditsData(): array
	{

		$crawlerUrl = env('VOIP_URL');

		$client = new Client();
		$crawler = $client->request('GET', $crawlerUrl);

		$form = $crawler->selectButton('Login')->form();
		$form['lusername'] = env('VOIP_USERNAME');
		$form['lpassword'] = env('VOIP_PASSWORD');

		$client->submit($form);
		$crawler = $client->request('GET', $crawlerUrl);
		$list = [];

		$i = 0;

		$crawler->filter('tr')->each(function ($node) use (&$list, &$i) {

			$i++;
			$c = 0;
			$empty = false;

			$node->filter('td')->each(function ($node2) use (&$c, &$i, &$list, &$empty) {

				if ($c == 0) {
					if (str_contains($node2->html(), '371')) {
						$list[$i]['phone'] = (int)$node2->html();
					} else {
						$empty = true;
					}
				} else if ($c == 3) {
					$list[$i]['credits'] = (int)$node2->html();
				}

				$c++;
			});

			if ($empty) {
				unset($list[$i]);
			}

		});

		return $list;
	}

}
