import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
} from 'typeorm';

@Entity('notification_logs')
export class NotificationLog {
  @PrimaryGeneratedColumn()
  id: number;

  @Column()
  recipient_email: string;

  @Column()
  template_name: string;

  @Column({ type: 'enum', enum: ['sent', 'failed'] })
  status: string;

  @CreateDateColumn()
  sent_at: Date;
}
