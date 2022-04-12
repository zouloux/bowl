<?php


class BowlAuthor
{
	public int $id;
	public string $displayName;
	public string $firstName;
	public string $lastName;
	public string $nickName;
	public string $niceName;
	public string $description;
	public string $url;
	public string $email;
	public string $login;
	public string $level;
	public string $avatarHref;

	public function __construct ( int $authorID ) {
		$this->description = get_the_author_meta("description", $authorID);
		$this->displayName = get_the_author_meta("display_name", $authorID);
		$this->firstName = get_the_author_meta("first_name", $authorID);
		$this->lastName = get_the_author_meta("last_name", $authorID);
		$this->nickName = get_the_author_meta("nickname", $authorID);
		$this->email = get_the_author_meta("user_email", $authorID);
		$this->level = intval( get_the_author_meta("user_level", $authorID) );
		$this->login = get_the_author_meta("user_login", $authorID);
		$this->niceName = get_the_author_meta("user_nicename", $authorID);
		$this->url = get_the_author_meta("user_url", $authorID);
		$this->avatarHref = get_avatar_url( $authorID );
	}
}