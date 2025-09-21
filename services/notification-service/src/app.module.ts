import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { AppController } from './app.controller';
import { AppService } from './app.service';
import { NotificationTemplate } from './entities/notification-template.entity';
import { NotificationLog } from './entities/notification-log.entity';
import { TemplateSeeder } from './seeder/template.seeder';

@Module({
  imports: [
    TypeOrmModule.forRoot({
      type: 'mysql',
      host: process.env.DB_HOST || 'localhost',
      port: Number(process.env.DB_PORT) || 3306,
      // Support both DB_USERNAME/DB_DATABASE (Laravel-style) and DB_USER/DB_NAME
      username: process.env.DB_USERNAME || process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_DATABASE || process.env.DB_NAME,
      entities: [NotificationTemplate, NotificationLog],
      synchronize: true,
    }),
    TypeOrmModule.forFeature([NotificationTemplate, NotificationLog]),
  ],
  controllers: [AppController],
  providers: [AppService, TemplateSeeder],
})
export class AppModule {}
