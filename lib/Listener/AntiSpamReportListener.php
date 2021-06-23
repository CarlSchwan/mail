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

use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Service\AntiSpamService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;

class AntiSpamReportListener implements IEventListener {

	/** @var MessageMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	/** @var IMailer */
	private $mailer;

	/** @var AntiSpamService */
	private $service;

	public function __construct(MessageMapper $mapper,
								IMailer $mailer,
								LoggerInterface $logger,
								AntiSpamService $service) {
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->service = $service;
	}

	public function handle(Event $event): void {
		if ($event instanceof MessageFlaggedEvent && $event->getFlag() === 'junk') {
			if (!$event->isSet()) {
				return;
			}

			$email = $this->service->getReportEmail();

			// No anti spam config found
			if (empty($email)) {
				return;
			}

			//Send message to reporting service
			$this->service->sendSpamReport(
				$event
			);
		}
	}
}
