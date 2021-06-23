<?php

declare(strict_types=1);

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Model\NewMessageData;
use OCP\IConfig;

class AntiSpamService {
	public const NAME = 'spamreport';

	/** @var IConfig */
	private $config;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var IMailTransmission */
	private $transmission;

	public function __construct(IConfig $config,
								MessageMapper $messageMapper,
								IMailTransmission $transmission) {
		$this->config = $config;
		$this->messageMapper = $messageMapper;
		$this->transmission = $transmission;
	}

	public function getReportEmail(): AddressList {
		return AddressList::fromRow(['label' => '', 'email' => $this->config->getAppValue('mail', self::NAME)]);
	}

	public function setReportEmail(string $email): void {
		$this->config->setAppValue('mail', self::NAME, $email);
	}

	public function sendSpamReport(MessageFlaggedEvent $event): void {
		$attachmentMessageId = $this->messageMapper->getIdForUid($event->getMailbox(), $event->getUid());
		$messageData = $this->createSpamMessageData($event, $attachmentMessageId);
		$this->transmission->sendMessage($messageData);
	}

	private function createSpamMessageData(MessageFlaggedEvent$event, int $id): NewMessageData {
		return new NewMessageData(
			$event->getAccount(),
			$this->getReportEmail(),
			new AddressList([]),
			new AddressList([]),
			self::NAME,
			'', // no content needed since it's only going to an automated spam service
			[['id' => $id, 'type' => 'message/rfc822']],
			false,
			false
		);
	}
}
