import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { getRepositoryToken } from '@nestjs/typeorm';
import { NotificationTemplate } from './entities/notification-template.entity';
import { faker } from '@faker-js/faker';
import type { Repository } from 'typeorm';

async function bootstrap(): Promise<void> {
  const app = await NestFactory.createApplicationContext(AppModule);
  const repo = app.get<Repository<NotificationTemplate>>(
    getRepositoryToken(NotificationTemplate),
  );

  const templates: Array<Partial<NotificationTemplate>> = [
    {
      name: 'welcome_email',
      subject: 'Welcome to White Pages for Tradies!',
      body: `Hi ${faker.person.firstName()}, welcome!`,
    },
    {
      name: 'booking_created_email',
      subject: 'New Booking Confirmation',
      body: 'Your booking has been confirmed.',
    },
    {
      name: 'job_completed_email',
      subject: 'Job Completed',
      body: 'Your job has been marked as completed.',
    },
    {
      name: 'review_submitted_email',
      subject: 'Thanks for your review',
      body: 'We appreciate your feedback.',
    },
  ];
  await repo.save(templates);
  await app.close();
}

void bootstrap();
