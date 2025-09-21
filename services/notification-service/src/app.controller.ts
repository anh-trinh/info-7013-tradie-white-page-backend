
import { Controller } from '@nestjs/common';
import { EventPattern, Payload } from '@nestjs/microservices';
import { AppService } from './app.service';

@Controller()
export class AppController {
  constructor(private readonly appService: AppService) {}

  @EventPattern('account_registered')
  handleAccountRegistered(@Payload() data: { email: string; name: string }) {
    console.log('Received account_registered event:', data);
    this.appService.sendWelcomeEmail(data);
  }

  @EventPattern('booking_created')
  handleBookingCreated(@Payload() data: any) {
    console.log('Received booking_created event:', data);
    // TODO: Call service to send booking notification email
  }

  @EventPattern('job_completed')
  handleJobCompleted(@Payload() data: any) {
    console.log('Received job_completed event:', data);
    // TODO: Call service to send job completion notification email
  }
}
