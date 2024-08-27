<?php

namespace RFlatti\ShopifyPlans\Models\Plans;

use RFlatti\ShopifyPlans\Models\Plans as PlanModel;

class Plans
{
    public function createOrUpdate(string $plan_name, int $plan_price, string $plan_description, int $id = null){
        $payload = [
            'plan_name' => $plan_name,
            'plan_price' => $plan_price,
            'plan_description' => $plan_description
        ];

        if($id == null){
            //create the plan
            $payload['id'] = $payload;
            $plan = new PlanModel();
            $plan->fill($payload);
            $plan->save();
            return $plan;
        } else {
            //update the plan
            $plan = PlanModel::find($id);
            if ($plan) {
                $plan->update($payload);
                return $plan;
            } else {
                throw new \Exception("Plan with ID $id not found.");
            }
        }
    }

    public function get(int $plan_id = null){
        if($plan_id == null){
            return PlanModel::where('id', $plan_id);
        }
        return PlanModel::all();
    }
}
