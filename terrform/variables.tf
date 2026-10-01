variable "ami" {
  type = string
  description = "Ubuntu 22.04 AMI"
  default = "ami-024e6efaf93d85776"
}

variable "instance_type" {
  type = string
  description = "Instance type"
  default = "t2.micro"
}

variable "name_tag" {
  type = string
  description = "Name tag"
  default = "nagi-dev"
}
