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
}
