<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConstantDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('measurement_types')->upsert([
            ['code'=>'count','name'=>'Count','description'=>'Numeric count','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'percentage','name'=>'Percentage','description'=>'Percentage value','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'ratio','name'=>'Ratio','description'=>'Ratio value','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'currency','name'=>'Currency','description'=>'Monetary value','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'decimal','name'=>'Decimal','description'=>'Decimal numeric value','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'yes_no','name'=>'Yes / No','description'=>'Boolean achievement','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ], ['code'], ['name','description','is_active','updated_at']);
        DB::table('units_of_measure')->upsert([
            ['code'=>'people','name'=>'People','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'women','name'=>'Women','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'children','name'=>'Children','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'households','name'=>'Households','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'groups','name'=>'Groups','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'organizations','name'=>'Organizations','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'accounts','name'=>'Accounts','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'cases','name'=>'Cases','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'activities','name'=>'Activities / Workstations','symbol'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'percent','name'=>'Percent','symbol'=>'%','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'tzs','name'=>'Tanzanian Shillings','symbol'=>'TZS','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'usd','name'=>'US Dollars','symbol'=>'USD','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ], ['code'], ['name','symbol','is_active','updated_at']);
        DB::table('organization_types')->upsert([
            ['code'=>'bank','name'=>'Bank / Financial Institution','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'government','name'=>'Government Institution','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'council','name'=>'Local Government Authority / Council','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'ngo','name'=>'Non-Governmental Organization','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'development_partner','name'=>'Development Partner','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'private_sector','name'=>'Private Sector','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ], ['code'], ['name','is_active','updated_at']);
        DB::table('data_sources')->upsert([
            ['code'=>'administrative','name'=>'Administrative Data','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'survey','name'=>'Survey','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'police','name'=>'Police Records','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'health','name'=>'Health Facility Records','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'council_mis','name'=>'Council MIS','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'school_records','name'=>'School Records','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'banking_report','name'=>'Banking / Financial Sector Report','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'activity_records','name'=>'Activity / Workstation Records','description'=>null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ], ['code'], ['name','description','is_active','updated_at']);
        $dimensions=[
            'gender'=>['Gender',[['female','Female'],['male','Male'],['unknown','Unknown / Not Reported']]],
            'age_group'=>['Age Group',[['0_17','0-17'],['18_24','18-24'],['25_34','25-34'],['35_44','35-44'],['45_plus','45+']]],
            'disability'=>['Disability',[['with_disability','With Disability'],['without_disability','Without Disability'],['not_reported','Not Reported']]],
        ];
        foreach($dimensions as $code=>$def){ DB::table('dimensions')->updateOrInsert(['code'=>$code],['name'=>$def[0],'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]); $id=DB::table('dimensions')->where('code',$code)->value('id'); foreach($def[1] as $i=>$opt){ DB::table('dimension_options')->updateOrInsert(['dimension_id'=>$id,'code'=>$opt[0]],['name'=>$opt[1],'sort_order'=>$i+1,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]); }}
        for($y=2025;$y<=2030;$y++){ $e=$y+1; $name=$y.'/'.substr((string)$e,-2); $start=Carbon::create($y,7,1); $end=Carbon::create($e,6,30); DB::table('financial_years')->updateOrInsert(['name'=>$name],['start_date'=>$start->toDateString(),'end_date'=>$end->toDateString(),'is_current'=>now()->between($start,$end),'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]); $fy=DB::table('financial_years')->where('name',$name)->value('id'); $qs=[['Q1','Quarter 1',Carbon::create($y,7,1),Carbon::create($y,9,30)],['Q2','Quarter 2',Carbon::create($y,10,1),Carbon::create($y,12,31)],['Q3','Quarter 3',Carbon::create($e,1,1),Carbon::create($e,3,31)],['Q4','Quarter 4',Carbon::create($e,4,1),Carbon::create($e,6,30)]]; foreach($qs as $i=>$q){ DB::table('reporting_periods')->updateOrInsert(['financial_year_id'=>$fy,'code'=>$q[0]],['name'=>$q[1],'period_type'=>'quarter','sequence'=>$i+1,'start_date'=>$q[2]->toDateString(),'end_date'=>$q[3]->toDateString(),'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]); }}
    }
}
