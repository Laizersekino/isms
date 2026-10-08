<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisciplinaryCaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'offence_type' => $this->faker->randomElement(['Late arrival', 'Absence', 'Misconduct', 'Book theft']),
            'incident_date' => $this->faker->date(),
            'description' => $this->faker->paragraph(),
            'reported_by' => User::factory(),
            'action_taken' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['open', 'resolved', 'closed']),
            'resolution_date' => null,
            'follow_up' => null,
            'created_by' => User::factory(),
        ];
    }
}