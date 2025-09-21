import { Controller } from '@nestjs/common';
import { EventPattern, Payload } from '@nestjs/microservices';
import { AppService } from './app.service';

@Controller()
export class AppController {
  constructor(private readonly appService: AppService) {}

  @EventPattern('account_registered')
  async handleAccountRegistered(
    @Payload() data: { email: string; name: string },
  ): Promise<void> {
    if (
      !data ||
      typeof data.email !== 'string' ||
      typeof data.name !== 'string'
    ) {
      // Safely throw an error
      throw new Error('Invalid payload for account_registered event');
    }
    console.log('Received account_registered event:', data);
    await this.appService.sendWelcomeEmail(
      data as { email: string; name: string },
    );
  }

  @EventPattern('booking_created')
  async handleBookingCreated(
    @Payload()
    data: {
      bookingId: string;
      userEmail: string;
      [key: string]: any;
    },
  ) {
    if (
      !data ||
      typeof data.bookingId !== 'string' ||
      typeof data.userEmail !== 'string'
    ) {
      throw new Error('Invalid payload for booking_created event');
    }
    console.log('Received booking_created event:', data);
    // TODO: Call service to send booking notification email
  }

  @EventPattern('job_completed')
  async handleJobCompleted(@Payload() data: any) {
    console.log('Received job_completed event:', data);
    // TODO: Call service to send job completion notification email
  }
}
