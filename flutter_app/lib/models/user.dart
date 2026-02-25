class User {
  final int id;
  final String name;
  final String email;
  final String? avatar;
  final String token;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.avatar,
    required this.token,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      avatar: json['avatar'],
      token: json['token'] ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'avatar': avatar,
        'token': token,
      };
}
