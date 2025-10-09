PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS Bus_Company (
    id TEXT PRIMARY KEY,                     -- UUID
    name TEXT NOT NULL UNIQUE,
    logo_path TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS User (
    id TEXT PRIMARY KEY,                     -- UUID
    full_name TEXT,
    email TEXT NOT NULL UNIQUE,
    role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
    password TEXT NOT NULL,
    company_id TEXT NULL,
    balance INTEGER DEFAULT 800,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

CREATE TABLE IF NOT EXISTS Trips (
    id TEXT PRIMARY KEY,                     -- UUID
    company_id TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    arrival_time DATETIME NOT NULL,
    departure_time DATETIME NOT NULL,
    departure_city TEXT NOT NULL,
    price INTEGER NOT NULL,
    capacity INTEGER NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

CREATE TABLE IF NOT EXISTS Tickets (
    id TEXT PRIMARY KEY,                     -- UUID
    trip_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'canceled', 'expired')),
    total_price INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES Trips(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);

CREATE TABLE IF NOT EXISTS Booked_Seats (
    id TEXT PRIMARY KEY,                     -- UUID
    ticket_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES Tickets(id)
);

CREATE TABLE IF NOT EXISTS Coupons (
    id TEXT PRIMARY KEY,                     -- UUID
    code TEXT NOT NULL,
    discount REAL NOT NULL,
    company_id TEXT NULL,
    usage_limit INTEGER NOT NULL,
    expire_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

CREATE TABLE IF NOT EXISTS User_Coupons (
    id TEXT PRIMARY KEY,                     -- UUID
    coupon_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES Coupons(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);
