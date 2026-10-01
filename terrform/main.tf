resource "aws_instance" "nagi_dev" {
    ami           = var.ami
    instance_type = var.instance_type
    tags = {
        Name = var.name_tag
    }
}