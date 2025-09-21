import { Controller } from '@nestjs/common';
import { EventPattern, Payload } from '@nestjs/microservices';
import { AppService } from './app.service';

@Controller()
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
  async handleBookingCreated(@Payload() data: any) {
    console.log('EVENT: booking_created', data);
    // TODO: send booking notification email
  }

  @EventPattern('job_completed')
  async handleJobCompleted(@Payload() data: any) {
    console.log('EVENT: job_completed', data);
    // TODO: send job completion notification email
  }

  @EventPattern('review_submitted')
  async handleReviewSubmitted(@Payload() data: any) {
    console.log('EVENT: review_submitted', data);
    // TODO: send thank-you email
  }
}
