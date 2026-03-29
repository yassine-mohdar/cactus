import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'package:path_provider/path_provider.dart';
import 'dart:io';

class DatabaseHelper {
  static const _databaseName = "NinoWorldDatabase.db";
  static const _databaseVersion = 1;

  // Singleton class
  DatabaseHelper._privateConstructor();
  static final DatabaseHelper instance = DatabaseHelper._privateConstructor();

  // Only have a single app-wide reference to the database
  static Database? _database;
  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDatabase();
    return _database!;
  }

  // Opens the database (and creates it if it doesn't exist)
  Future<Database> _initDatabase() async {
    // Platform-specific directories where app data can be saved
    Directory documentsDirectory = await getApplicationDocumentsDirectory();
    String path = join(documentsDirectory.path, _databaseName);
    return await openDatabase(
      path,
      version: _databaseVersion,
      onCreate: _onCreate,
    );
  }

  // SQL code to create the initial tables
  Future _onCreate(Database db, int version) async {
    // Example: Caching for the Feed/Community posts
    await db.execute('''
          CREATE TABLE cached_posts (
            id TEXT PRIMARY KEY,
            content TEXT NOT NULL,
            user TEXT NOT NULL,
            likes INTEGER NOT NULL,
            timestamp INTEGER NOT NULL
          )
          ''');

    // Additional tables can be created here (e.g., cached_friends, messages, etc.)
  }

  // Example insert method
  Future<int> insertPost(Map<String, dynamic> row) async {
    Database db = await instance.database;
    return await db.insert(
      'cached_posts',
      row,
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  // Example query method
  Future<List<Map<String, dynamic>>> queryAllPosts() async {
    Database db = await instance.database;
    return await db.query('cached_posts', orderBy: 'timestamp DESC');
  }

  // Clear cache
  Future<void> clearCache() async {
    Database db = await instance.database;
    await db.delete('cached_posts');
  }
}
