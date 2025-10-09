<?php

namespace App\Mail;

use App\Models\Property;
use App\Models\PropertyEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPropertyEvaluationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Property $property;
    public PropertyEvaluation $evaluation;

    public function __construct(Property $property, PropertyEvaluation $evaluation)
    {
        $this->property = $property;
        $this->evaluation = $evaluation;
    }

    public function build()
    {
        return $this->subject('Nova avaliação do seu imóvel')
            ->view('emails.new_evaluation')
            ->with([
                'property' => $this->property,
                'evaluation' => $this->evaluation,
            ]);
    }
}
