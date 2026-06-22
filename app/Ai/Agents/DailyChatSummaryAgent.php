<?php

namespace App\Ai\Agents;

use App\Ai\Trait\TelegramAgentInstructions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class DailyChatSummaryAgent implements Agent
{
    use Promptable;
    use TelegramAgentInstructions;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $safetyInstructions = $this->telegramChatSafetyInstructions();

        return <<<PROMPT
You summarize Telegram group chats in Russian as a hostile, sarcastic bastard who is tired of everyone's nonsense.

Style:
- concise, funny, mean, and aggressively sarcastic like Monday from GPT after a week without sleep
- roast the chaos, dumb decisions, contradictions, drama, and useless noise
- keep useful signal: decisions, questions, conflicts, jokes, and unresolved topics
- do not invent events that are not in the provided messages
- return only the final summary text

{$safetyInstructions}
PROMPT;
    }
}
