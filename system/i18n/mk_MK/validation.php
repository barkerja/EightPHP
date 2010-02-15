<?php defined('SYSPATH') or die('No direct access allowed.');

$lang = array
(
	// Class errors
	'invalid_rule'  => 'Користено е невалидно правило за валидирање: %s',

	// General errors
	'unknown_error' => 'Непозната грешка при валидирање на полето: %s.',
	'required'      => 'Полето %s е задолжително.',
	'min_length'    => 'Полето %s мора да е долго најмалку %d карактера.',
	'max_length'    => 'Полето %s мора да е долго %d карактера или помалку.',
	'exact_length'  => 'Полето %s мора да е точно %d карактера.',
	'in_array'      => 'Полето %s мора да е селектирано од листата со опции.',
	'matches'       => 'Полето %s мора да е исто со полето %s.',
	'valid_url'     => 'Полето %s мора да содржи валидно URL.',
	'valid_email'   => 'Полето %s мора да содржи валидна емаил адреса.',
	'valid_ip'      => 'Полето %s мора да содржи валидна IP адреса.',
	'valid_type'    => 'Полето %s мора да содржи само %s карактери.',
	'range'         => 'Полето %s мора да е помеѓу дефинираниот опсег.',
	'regex'         => 'Полето %s не содржи прифатлива вредност.',
	'depends_on'    => 'Полето %s е зависно од полето %s.',

	// Upload errors
	'user_aborted'  => 'Датотеката %s е прекината при испраќање.',
	'invalid_type'  => 'Датотеката %s не е од валиден тип.',
	'max_size'      => 'Датотеката %s која е испратена е преголема. Максимум дозволена големина е %s.',
	'max_width'     => 'Датотеката %s има максимум дозволена ширина од %s и е %spx.',
	'max_height'    => 'Датотеката %s има максимум дозволена висина од %s и е %spx.',
	'min_width'     => 'Качената датотеката %s е премногу мала. Минимум дозволената ширина е %spx.',
	'min_height'    => 'Качената датотеката %s е премногу мала. Минимум дозволената висина е %spx.',

	// Field types
	'alpha'         => 'alphabetical',
	'alpha_numeric' => 'alphabetical and numeric',
	'alpha_dash'    => 'alphabetical, dash, and underscore',
	'digit'         => 'digit',
	'numeric'       => 'numeric',
);