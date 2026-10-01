output "public_ip" {
  value = aws_instance.nagi_dev.public_ip
  description = "Public IP of the EC2 instance"
}

output "instance_id" {
  value = aws_instance.nagi_dev.id
  description = "Instance ID of the EC2 instance"
}
