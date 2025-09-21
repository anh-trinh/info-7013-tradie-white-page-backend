import { Controller } from '@nestjs/common';
import { EventPattern, Payload } from '@nestjs/microservices';
import { AppService } from './app.service';

@Controller('api/notifications')
export class AppController {
  constructor(private readonly appService: AppService) {}

  @EventPattern('account_registered')
  async handleAccountRegistered(
    @Payload() data: { email?: string; first_name?: string; name?: string },
  ): Promise<void> {
    if (!data || typeof data.email !== 'string') return;
    const name = data.first_name ?? data.name ?? '';
    console.log('EVENT: account_registered', { email: data.email, name });
    await this.appService.sendWelcomeEmail({ email: data.email, name });
  }

  @EventPattern('booking_created')
  async handleBookingCreated(@Payload() payload: unknown) {
    const data = (payload ?? {}) as Record<string, unknown>;
    console.log('EVENT: booking_created', data);
    await this.appService.logBookingCreated(data);
  }

  @EventPattern('job_completed')
  async handleJobCompleted(@Payload() payload: unknown) {
    const data = (payload ?? {}) as Record<string, unknown>;
    console.log('EVENT: job_completed', data);
    await this.appService.logJobCompleted(data);
  }

  @EventPattern('review_submitted')
  async handleReviewSubmitted(@Payload() payload: unknown) {
    const data = (payload ?? {}) as Record<string, unknown>;
    console.log('EVENT: review_submitted', data);
    await this.appService.logReviewSubmitted(data);
  }
}
