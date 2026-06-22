<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function count;
use function in_array;
use function sprintf;
use function strtolower;
use const T_ABSTRACT;
use const T_ATTRIBUTE_END;
use const T_CLASS;
use const T_COMMA;
use const T_FINAL;
use const T_FUNCTION;
use const T_OPEN_PARENTHESIS;
use const T_READONLY;
use const T_VARIABLE;
use const T_WHITESPACE;

class ReadonlyClassSniff implements Sniff
{

	public const CODE_CLASS_CAN_BE_READONLY = 'ClassCanBeReadonly';

	public const CODE_PROMOTED_PROPERTY_CANNOT_BE_READONLY_IN_READONLY_CLASS = 'PromotedPropertyCannotBeReadonlyInReadonlyClass';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_CLASS];
	}

	public function process(File $phpcsFile, int $classPointer): void
	{
		$constructorPointer = $this->findConstructorPointer($phpcsFile, $classPointer);
		if ($constructorPointer === null) {
			return;
		}

		$promotedProperties = $this->getPromotedProperties($phpcsFile, $constructorPointer);
		if (count($promotedProperties) === 0) {
			return;
		}

		$tokens = $phpcsFile->getTokens();
		if ($this->isReadonlyClass($phpcsFile, $classPointer)) {
			foreach ($promotedProperties as $promotedProperty) {
				$readonlyModifierPointer = $promotedProperty['readonlyModifierPointer'];
				if ($readonlyModifierPointer === null) {
					continue;
				}

				$fix = $phpcsFile->addFixableError(
					sprintf(
						'Promoted property %s in readonly class cannot be declared as readonly.',
						$tokens[$promotedProperty['propertyPointer']]['content'],
					),
					$readonlyModifierPointer,
					self::CODE_PROMOTED_PROPERTY_CANNOT_BE_READONLY_IN_READONLY_CLASS,
				);

				if (!$fix) {
					continue;
				}

				$phpcsFile->fixer->beginChangeset();
				$this->removeReadonlyModifier($phpcsFile, $readonlyModifierPointer);
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		foreach ($promotedProperties as $promotedProperty) {
			if ($promotedProperty['readonlyModifierPointer'] === null) {
				return;
			}
		}

		$fix = $phpcsFile->addFixableError(
			sprintf('Class %s can be marked as readonly.', ClassHelper::getName($phpcsFile, $classPointer)),
			$classPointer,
			self::CODE_CLASS_CAN_BE_READONLY,
		);

		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();

		foreach ($promotedProperties as $promotedProperty) {
			$this->removeReadonlyModifier($phpcsFile, $promotedProperty['readonlyModifierPointer']);
		}

		$phpcsFile->fixer->addContentBefore($classPointer, 'readonly ');

		$phpcsFile->fixer->endChangeset();
	}

	private function findConstructorPointer(File $phpcsFile, int $classPointer): ?int
	{
		$tokens = $phpcsFile->getTokens();
		$classLevel = $tokens[$classPointer]['level'];

		for ($i = $tokens[$classPointer]['scope_opener'] + 1; $i < $tokens[$classPointer]['scope_closer']; $i++) {
			if ($tokens[$i]['code'] !== T_FUNCTION) {
				continue;
			}

			if ($tokens[$i]['level'] !== $classLevel + 1) {
				continue;
			}

			if (strtolower(FunctionHelper::getName($phpcsFile, $i)) !== '__construct') {
				continue;
			}

			return $i;
		}

		return null;
	}

	private function isReadonlyClass(File $phpcsFile, int $classPointer): bool
	{
		$tokens = $phpcsFile->getTokens();

		$modifierPointer = TokenHelper::findPreviousEffective($phpcsFile, $classPointer - 1);
		while (
			$modifierPointer !== null
			&& in_array($tokens[$modifierPointer]['code'], [T_FINAL, T_ABSTRACT, T_READONLY], true)
		) {
			if ($tokens[$modifierPointer]['code'] === T_READONLY) {
				return true;
			}

			$modifierPointer = TokenHelper::findPreviousEffective($phpcsFile, $modifierPointer - 1);
		}

		return false;
	}

	/**
	 * @return list<array{propertyPointer: int, readonlyModifierPointer: ?int}>
	 */
	private function getPromotedProperties(File $phpcsFile, int $constructorPointer): array
	{
		$tokens = $phpcsFile->getTokens();
		$promotedProperties = [];

		for ($i = $tokens[$constructorPointer]['parenthesis_opener'] + 1; $i < $tokens[$constructorPointer]['parenthesis_closer']; $i++) {
			if ($tokens[$i]['code'] !== T_VARIABLE) {
				continue;
			}

			$pointerBefore = TokenHelper::findPrevious($phpcsFile, [T_COMMA, T_OPEN_PARENTHESIS, T_ATTRIBUTE_END], $i - 1);
			$modifierPointer = TokenHelper::findNextEffective($phpcsFile, $pointerBefore + 1);
			if (!in_array($tokens[$modifierPointer]['code'], TokenHelper::PROPERTY_MODIFIERS_TOKEN_CODES, true)) {
				continue;
			}

			$readonlyModifierPointer = TokenHelper::findNext($phpcsFile, T_READONLY, $modifierPointer, $i);

			$promotedProperties[] = [
				'propertyPointer' => $i,
				'readonlyModifierPointer' => $readonlyModifierPointer,
			];
		}

		return $promotedProperties;
	}

	private function removeReadonlyModifier(File $phpcsFile, int $readonlyModifierPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		$phpcsFile->fixer->replaceToken($readonlyModifierPointer, '');

		$nextPointer = TokenHelper::findNext($phpcsFile, T_WHITESPACE, $readonlyModifierPointer + 1, $readonlyModifierPointer + 2);
		if ($nextPointer !== null) {
			$phpcsFile->fixer->replaceToken($nextPointer, '');
		}
	}

}
