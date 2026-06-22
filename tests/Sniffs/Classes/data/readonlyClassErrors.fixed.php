<?php // lint >= 8.2

readonly class Candidate
{

	public function __construct(
		private int $id,
		public string $name,
	) {
	}

}

readonly class InvalidReadonly
{

	public function __construct(
		private int $id,
		protected string $name,
	) {
	}

}

final readonly class FinalCandidate
{

	public function __construct(private int $id)
	{
	}

}
