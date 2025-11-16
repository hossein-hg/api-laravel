<?php
$result = [];
foreach ($filters as $filter) {
    $options = FilterOptions::where('filter_id',$filter->id)->get();
    $result[$filter->name] = $options;

}


['filer1'=>['option1','option2'],];
['filer2'=>['option3','option4'],];
['filer3'=>['option5','option6'],];
['filer4'=>['option7','option8'],];