<?php

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

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\SentMailboxNotSetException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AntiSpamService;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class AntiSpamServiceTest extends TestCase {

	/** @var AntiSpamService */
	private $service;

	/** @var IConfig|MockObject */
	private $config;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var IMailTransmission|MockObject */
	private $transmission;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createConfiguredMock(IConfig::class, [
			'getAppValue' => 'test@test.com'
		]);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->transmission = $this->createMock(IMailTransmission::class);

		$this->service = new AntiSpamService(
			$this->config,
			$this->messageMapper,
			$this->transmission
		);
	}

	public function testCreateSpamReportMessageDataMessageNotFound() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$this->messageMapper->expects($this->once())
			->method('getIdForUid')
			->with($mailbox, 1)
			->willReturn(null);
		$this->expectException(ServiceException::class);
		$this->service->createSpamReportMessageData($account,$mailbox,1);
	}

	public function testCreateSpamReportMessageData() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$this->messageMapper->expects($this->once())
			->method('getIdForUid')
			->with($mailbox, 1)
			->willReturn(1);

		$expected = NewMessageData::fromRequest(
			$account,
			'test@test.com',
			null,
			null,
			'antispam_reporting',
			'',
			[['id' => 1, 'type' => 'message/rfc822']],
		);
		$actual = $this->service->createSpamReportMessageData($account,$mailbox,1);

		$this->assertEquals($expected,$actual);
	}

	public function testSendSpamReport(){
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest(
			$account,
			'test@test.com',
			null,
			null,
			'antispam_reporting',
			'',
			[['id' => 1, 'type' => 'message/rfc822']],
		);
		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData);
		$this->service->sendSpamReport($messageData);
	}

	public function testSendSpamReportServiceException(){
		$account = $this->createMock(Account::class);
		$messageData = $this->createMock(NewMessageData::class);

		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData)
			->willThrowException(new ServiceException());
		$this->expectException(ServiceException::class);

		$this->service->sendSpamReport($messageData);
	}

	public function testSendSpamReportSentMailboxNotSetException(){
		$account = $this->createMock(Account::class);
		$messageData = $this->createMock(NewMessageData::class);

		$this->transmission->expects($this->once())
			->method('sendMessage')
			->with($messageData)
			->willThrowException(new SentMailboxNotSetException());
		$this->expectException(ServiceException::class);

		$this->service->sendSpamReport($messageData);
	}
}
