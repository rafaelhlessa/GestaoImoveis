<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('plan_id')
                  ->constrained('subscription_plans');
            $table->string('gateway_subscription_id')
                  ->comment('ID retornado pelo gateway');
            $table->enum('status', [
                'active','past_due','canceled','expired'
            ])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')
                  ->nullable();
            $table->timestamps();

            $table->index(['user_id', 'plan_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
