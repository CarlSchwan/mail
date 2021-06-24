<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author Anna Larch <anna@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AntiSpamService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

class AntiSpamReportListener implements IEventListener {

	/** @var LoggerInterface */
	private $logger;

	/** @var AntiSpamService */
	private $service;

	public function __construct(LoggerInterface $logger,
								AntiSpamService $service) {
		$this->logger = $logger;
		$this->service = $service;
	}

	public function handle(Event $event): void {
		if ($event instanceof MessageFlaggedEvent && $event->getFlag() === 'junk') {
			if (!$event->isSet()) {
				return;
			}

			// No anti spam config found
			if (empty($this->service->getReportEmail())) {
				return;
			}

			//Send message to reporting service
			try {
				$message = $this->service->createSpamReportMessageData($event->getAccount(), $event->getMailbox(), $event->getUid());
				$this->service->sendSpamReport($message);
			} catch (ServiceException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
		}
	}
}
