<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author Anna Larch <anna@nextcloud.com>
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

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\SentMailboxNotSetException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Model\NewMessageData;
use OCP\IConfig;

class AntiSpamService {
	public const NAME = 'antispam_reporting';
	public const MESSAGE_TYPE = 'message/rfc822';

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

	/**
	 * @codeCoverageIgnore
	 */
	public function getReportEmail(): string {
		return $this->config->getAppValue('mail', self::NAME);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setReportEmail(string $email): void {
		$this->config->setAppValue('mail', self::NAME, $email);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function deleteConfig(): void {
		$this->config->deleteAppValue('mail', self::NAME);
	}

	/**
	 * @param NewMessageData $messageData
	 * @throws ServiceException
	 */
	public function sendSpamReport(NewMessageData $messageData): void {
		if ($this->getReportEmail() === null) {
			throw new ServiceException('Antispam service email not configured', 0, []);
		}

		try {
			$this->transmission->sendMessage($messageData);
		} catch (SentMailboxNotSetException | ServiceException $e) {
			throw new ServiceException('Could not send spam report', 0, $e);
		}
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $uid
	 * @return NewMessageData
	 * @throws ServiceException
	 */
	public function createSpamReportMessageData(Account $account, Mailbox $mailbox, int $uid): NewMessageData {
		$attachedMessageId = $this->messageMapper->getIdForUid($mailbox, $uid);
		if ($attachedMessageId === null) {
			throw new ServiceException('Could not find reported message', 0, []);
		}

		return NewMessageData::fromRequest(
			$account,
			$this->config->getAppValue('mail', self::NAME),
			null,
			null,
			self::NAME,
			'',
			[['id' => $attachedMessageId, 'type' => self::MESSAGE_TYPE]],
		);
	}
}
