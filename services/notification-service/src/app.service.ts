import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { NotificationLog } from './entities/notification-log.entity';
import { NotificationTemplate } from './entities/notification-template.entity';

@Injectable()
export class AppService {
  constructor(
    @InjectRepository(NotificationLog)
    private logsRepository: Repository<NotificationLog>,
    @InjectRepository(NotificationTemplate)
    private templatesRepository: Repository<NotificationTemplate>,
  ) {}

  private extractEmail(
    payload: Readonly<Record<string, unknown>>,
  ): string | undefined {
    const value = payload['email'];
    return typeof value === 'string' ? value : undefined;
  }

  async sendWelcomeEmail(data: { email: string; name: string }): Promise<void> {
    const template = await this.templatesRepository.findOneBy({
      name: 'welcome_email',
    });

    if (!template) {
      console.error('Welcome email template not found!');
      return;
    }

    // Simulate sending email
    console.log(`--- Sending Email ---`);
    console.log(`To: ${data.email}`);
    console.log(`Subject: ${template.subject}`);
    console.log(`Body: ${template.body.replace('{{name}}', data.name)}`);
    console.log(`---------------------`);

    // Log to database
    const log = this.logsRepository.create({
      recipient_email: data.email,
      template_name: 'welcome_email',
      status: 'sent',
    });
    await this.logsRepository.save(log);
  }

  async logBookingCreated(data: Record<string, unknown>): Promise<void> {
    const template = await this.templatesRepository.findOneBy({
      name: 'booking_created',
    });
    if (!template) {
      console.error('booking_created template not found!');
      await this.logsRepository.save(
        this.logsRepository.create({
          recipient_email: '-',
          template_name: 'booking_created',
          status: 'failed',
        }),
      );
      return;
    }
    const recipient = this.extractEmail(data) ?? '-';
    console.log('--- Sending Email ---');
    console.log(`To: ${recipient}`);
    console.log(`Subject: ${template.subject}`);
    console.log(`Body: ${template.body}`);
    console.log('---------------------');
    await this.logsRepository.save(
      this.logsRepository.create({
        recipient_email: recipient,
        template_name: 'booking_created',
        status: 'sent',
      }),
    );
  }

  async logJobCompleted(data: Record<string, unknown>): Promise<void> {
    const template = await this.templatesRepository.findOneBy({
      name: 'job_completed',
    });
    if (!template) {
      console.error('job_completed template not found!');
      await this.logsRepository.save(
        this.logsRepository.create({
          recipient_email: '-',
          template_name: 'job_completed',
          status: 'failed',
        }),
      );
      return;
    }
    const recipient = this.extractEmail(data) ?? '-';
    console.log('--- Sending Email ---');
    console.log(`To: ${recipient}`);
    console.log(`Subject: ${template.subject}`);
    console.log(`Body: ${template.body}`);
    console.log('---------------------');
    await this.logsRepository.save(
      this.logsRepository.create({
        recipient_email: recipient,
        template_name: 'job_completed',
        status: 'sent',
      }),
    );
  }

  async logReviewSubmitted(data: Record<string, unknown>): Promise<void> {
    const template = await this.templatesRepository.findOneBy({
      name: 'review_submitted',
    });
    if (!template) {
      console.error('review_submitted template not found!');
      await this.logsRepository.save(
        this.logsRepository.create({
          recipient_email: '-',
          template_name: 'review_submitted',
          status: 'failed',
        }),
      );
      return;
    }
    const recipient = this.extractEmail(data) ?? '-';
    console.log('--- Sending Email ---');
    console.log(`To: ${recipient}`);
    console.log(`Subject: ${template.subject}`);
    console.log(`Body: ${template.body}`);
    console.log('---------------------');
    await this.logsRepository.save(
      this.logsRepository.create({
        recipient_email: recipient,
        template_name: 'review_submitted',
        status: 'sent',
      }),
    );
  }
}
