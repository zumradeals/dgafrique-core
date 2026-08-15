<?php
declare(strict_types=1);
namespace App\Application\Projects;
final class ProjectList { public static function fromText(?string $value,int $limit=20): array { if($value===null)return []; return array_slice(array_values(array_unique(array_filter(array_map(static fn(string $v): string=>trim($v),preg_split('/\r\n|\r|\n/', $value)?:[])))),$limit*-1); } }
