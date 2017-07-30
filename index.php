<?php
require_once('functions.php');

switch ($action) {

    default:

        //getting json from https://codetest.kube.getswift.co/drones
        $json = file_get_contents('https://codetest.kube.getswift.co/drones');
        $objs = json_decode($json, true);
        $objs = remove_duplicates($objs);

        //getting list of drones with no packages
        foreach($objs as $obj) {
            if(!empty($obj->packages)) {

                //distance from current location to destination
                $distance = haversineGreatCircleDistance(
                    $obj->location->latitude,
                    $obj->location->longitude,
                    $obj->packages[0]->destination->latitude,
                    $obj->packages[0]->destination->longitude
                );

                //adding return distance
                $distance += haversineGreatCircleDistance(
                    $obj->packages[0]->destination->latitude,
                    $obj->packages[0]->destination->longitude,
                    -37.816664,
                    144.963848
                );
                //adding distance param to object
                $obj->distance = $distance;
            }
            else {

                //calculating remaining return distance
                $distance = haversineGreatCircleDistance(
                    $obj->location->latitude,
                    $obj->location->longitude,
                    -37.816664,
                    144.963848
                );
                //adding distance param to object
                $obj->distance = $distance;
            }
        }

        //sorting drones by shortest distance first
        usort($objs, function($a, $b)
        {
            return strcmp($a->distance, $b->distance);
        });

        //list of packages from https://codetest.kube.getswift.co/packages
        $json_pkg = file_get_contents('https://codetest.kube.getswift.co/packages');
        $objs_pkg = json_decode($json_pkg);

        //sorting packages by lowest deadline
        usort($objs_pkg, function($a, $b)
        {
            return strcmp($a->deadline, $b->deadline);
        });

        //looping through drones to assign packages to them using an `assignments` array
        //we're starting with the soonest available drone and assigning it the package with nearest deadline
        //as soon as we arrive to a drone that cannot perform the next operation it means all succeeding drones cannot as well
        //at which point we can break the operation
        $pkg_count = count($objs_pkg);
        foreach($objs as $key => $obj){
            //checking if we reached package count
            if ($key == $pkg_count) break;
            $assignments[$key] = ['droneId' => $obj->droneId, 'packageId' => $objs_pkg[0]->packageId];
            //removing package from package objects
            array_shift($objs_pkg);
        }

        //creating array of remaining packages
        $packages = [];
        foreach($objs_pkg as $pkg) {
            array_push($packages, $pkg->packageId);
        }
        $unassignedPackageIds = $packages;

        //creating a result variable that holds the assignments and the unassigned packages
        $result = array("assignments" => $assignments, "unassignedPackageIds" => $unassignedPackageIds);

        //converting result to json
        $myJSON = json_encode($result);
        echo($myJSON);

        break;


}
?>