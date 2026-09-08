<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SplFileObject;
class TamisemiLocationSeeder extends Seeder
{
 public function run(): void
 {
  $path=database_path('seeders/Administrative hierachy.csv');
  if(!file_exists($path)){ $this->command?->error("CSV file not found: {$path}"); return; }
  $file=new SplFileObject($path,'r'); $file->setFlags(SplFileObject::READ_CSV|SplFileObject::SKIP_EMPTY); $headers=null;
  DB::beginTransaction();
  try{
   foreach($file as $row){ if(!is_array($row)||$row===[null]) continue; if($headers===null){ $headers=array_map(fn($v)=>$this->clean($v),$row); continue; } $record=array_combine($headers,array_pad($row,count($headers),null)); if(!$record) continue;
    $r=$this->upsert('region','region_id',$this->clean($record['region_code']??null),$this->clean($record['region_name']??null));
    $d=$this->upsert('district','district_id',$this->clean($record['district_code']??null),$this->clean($record['district_name']??null),['region_id'=>$r->region_id??null]);
    $c=$this->upsert('council','council_id',$this->clean($record['council_code']??null),$this->clean($record['council_name']??null),['district_id'=>$d->district_id??null]);
    $dv=$this->upsert('division','division_id',$this->clean($record['division_code']??null),$this->clean($record['division_name']??null),['council_id'=>$c->council_id??null]);
    $t=$this->upsert('township','township_id',$this->clean($record['township_code']??null),$this->clean($record['township_name']??null),['division_id'=>$dv->division_id??null]);
    $w=$this->upsert('ward','ward_id',$this->clean($record['ward_code']??null),$this->clean($record['ward_name']??null),['district_id'=>$d->district_id??null,'council_id'=>$c->council_id??null]);
    $v=$this->upsert('village_mtaa','village_mtaa_id',$this->clean($record['village_mtaa_code']??null),$this->clean($record['village_mtaa_name']??null),['ward_id'=>$w->ward_id??null]);
    $this->upsert('kitongoji','kitongoji_id',$this->clean($record['kitongoji_code']??null),$this->clean($record['kitongoji_name']??null),['village_mtaa_id'=>$v->village_mtaa_id??null]);
   }
   DB::commit(); $this->command?->info('TAMISEMI locations seeded successfully.');
  }catch(\Throwable $e){ DB::rollBack(); $this->command?->error('Seeding failed: '.$e->getMessage()); throw $e; }
 }
 private function upsert(string $table,string $pk,?string $code,?string $name,array $parents=[]): ?object
 {
  if(!$code&&!$name) return null; $q=DB::table($table); $row=$code?(clone $q)->where('code',$code)->first():null;
  if(!$row&&$name){ $by=(clone $q)->where('name',$name); foreach($parents as $col=>$val)$by->where($col,$val); $row=$by->first(); }
  $payload=array_merge(['name'=>$name,'updated_at'=>now()],$parents); if($code)$payload['code']=$code; $payload=array_filter($payload,fn($v)=>$v!==null);
  if($row){ DB::table($table)->where($pk,$row->{$pk})->update($payload); return DB::table($table)->where($pk,$row->{$pk})->first(); }
  if(!$name)return null; $payload['created_at']=now(); $id=DB::table($table)->insertGetId($payload,$pk); return DB::table($table)->where($pk,$id)->first();
 }
 private function clean($value): ?string
 {
  if($value===null)return null; $value=trim((string)$value); if($value===''||strtoupper($value)==='NULL')return null; $value=mb_convert_encoding($value,'UTF-8','UTF-8, Windows-1252, ISO-8859-1, ASCII'); $value=preg_replace('/[^\P{C}\n\r\t]+/u','',$value); return $value?:null;
 }
}
