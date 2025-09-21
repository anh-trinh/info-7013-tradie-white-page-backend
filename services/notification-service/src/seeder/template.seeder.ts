import { Injectable, OnApplicationBootstrap } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { NotificationTemplate } from '../entities/notification-template.entity';

@Injectable()
export class TemplateSeeder implements OnApplicationBootstrap {
  constructor(
    @InjectRepository(NotificationTemplate)
    private readonly templatesRepo: Repository<NotificationTemplate>,
  ) {}

  async onApplicationBootstrap(): Promise<void> {
    try {
      const count = await this.templatesRepo.count();
      if (count > 0) return; // already seeded

      const templates: Array<Partial<NotificationTemplate>> = [
        {
          name: 'welcome_email',
          subject: 'Welcome, {{name}}!',
          body: "Hi {{name}},\n\nThanks for joining our platform. We're excited to have you on board!\n\nBest regards,\nTradie Team",
        },
        {
          name: 'booking_created',
          subject: 'Your booking has been created',
          body: "Hello,\n\nYour booking has been created successfully. We'll notify you with updates.\n\nThanks,\nTradie Team",
        },
        {
          name: 'job_completed',
          subject: 'Job completed successfully',
          body: 'Hello,\n\nYour job has been marked as completed. We hope everything went well!\n\nThanks,\nTradie Team',
        },
        {
          name: 'review_submitted',
          subject: 'Thanks for your review',
          body: 'Hello,\n\nThanks for submitting your review. Your feedback helps us improve.\n\nBest,\nTradie Team',
        },
      ];

      await this.templatesRepo.save(templates);

      console.log(
        '[notification-service] Seeded default notification templates',
      );
    } catch (e) {
      console.error('[notification-service] Template seeding failed:', e);
    }
  }
}
