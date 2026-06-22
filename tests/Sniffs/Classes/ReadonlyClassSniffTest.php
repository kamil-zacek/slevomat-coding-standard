<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use SlevomatCodingStandard\Sniffs\TestCase;

class ReadonlyClassSniffTest extends TestCase
{

	public function testNoErrors(): void
	{
		$report = self::checkFile(__DIR__ . '/data/readonlyClassNoErrors.php');
		self::assertNoSniffErrorInFile($report);
	}

	public function testErrors(): void
	{
		$report = self::checkFile(__DIR__ . '/data/readonlyClassErrors.php');

		self::assertSame(4, $report->getErrorCount());

		self::assertSniffError(
			$report,
			3,
			ReadonlyClassSniff::CODE_CLASS_CAN_BE_READONLY,
			'Class Candidate can be marked as readonly.',
		);

		self::assertSniffError(
			$report,
			18,
			ReadonlyClassSniff::CODE_PROMOTED_PROPERTY_CANNOT_BE_READONLY_IN_READONLY_CLASS,
			'Promoted property $id in readonly class cannot be declared as readonly.',
		);

		self::assertSniffError(
			$report,
			19,
			ReadonlyClassSniff::CODE_PROMOTED_PROPERTY_CANNOT_BE_READONLY_IN_READONLY_CLASS,
			'Promoted property $name in readonly class cannot be declared as readonly.',
		);

		self::assertSniffError(
			$report,
			25,
			ReadonlyClassSniff::CODE_CLASS_CAN_BE_READONLY,
			'Class FinalCandidate can be marked as readonly.',
		);

		self::assertAllFixedInFile($report);
	}

}
