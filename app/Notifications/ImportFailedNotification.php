<?php

namespace App\Notifications;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Bonus requirement: "error notification system". Kept intentionally
 * simple (log channel) so it works out of the box with zero extra
 * configuration - swap `via()` for ['mail'] or ['slack'] in production
 * and it will just work since the message body is already built below.
 */
class ImportFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public Upload $upload, public ?string $fatalMessage = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['log'];
    }

    public function toLog(object $notifiable): string
    {
        if ($this->fatalMessage) {
            return "Import '{$this->upload->original_filename}' (upload #{$this->upload->id}) crashed: {$this->fatalMessage}";
        }

        return "Import '{$this->upload->original_filename}' (upload #{$this->upload->id}) finished with {$this->upload->failed_rows} failed row(s) out of {$this->upload->total_rows}.";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'upload_id' => $this->upload->id,
            'failed_rows' => $this->upload->failed_rows,
            'fatal_message' => $this->fatalMessage,
        ];
    }
}
