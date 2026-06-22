<?php // lint >= 8.2

class Candidate
{

	public function __construct(
		private readonly int $id,
		public readonly string $name,
	) {
	}

}

readonly class InvalidReadonly
{

	public function __construct(
		private readonly int $id,
		protected readonly string $name,
	) {
	}

}

final class FinalCandidate
{

	public function __construct(private readonly int $id)
	{
	}

}
