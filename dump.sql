--
-- PostgreSQL database dump
--

\restrict SyOStcySHhaYMGVEoabZWE32Fyz5nVq10Nx7acRLuQMzYiVVjKY1XuyOdLZitYL

-- Dumped from database version 15.17 (Debian 15.17-1.pgdg13+1)
-- Dumped by pg_dump version 15.17 (Debian 15.17-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO "user";

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO "user";

--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO "user";

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.failed_jobs_id_seq OWNER TO "user";

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO "user";

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO "user";

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobs_id_seq OWNER TO "user";

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO "user";

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO "user";

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: order_items; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.order_items (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    "position" smallint DEFAULT '0'::smallint NOT NULL,
    product_name character varying(255) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    size_override character varying(255),
    line_comment character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.order_items OWNER TO "user";

--
-- Name: order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.order_items_id_seq OWNER TO "user";

--
-- Name: order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.order_items_id_seq OWNED BY public.order_items.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    user_id bigint,
    status character varying(32) DEFAULT 'pending'::character varying NOT NULL,
    child_full_name character varying(255) NOT NULL,
    child_gender character varying(16) NOT NULL,
    settlement character varying(255) NOT NULL,
    school character varying(255) NOT NULL,
    class_num character varying(255) NOT NULL,
    class_letter character varying(255),
    school_year character varying(255) NOT NULL,
    size_from_table character varying(255) NOT NULL,
    height_cm character varying(255),
    chest_cm character varying(255),
    waist_cm character varying(255),
    hips_cm character varying(255),
    figure_comment text,
    kit_comment text,
    parent_full_name character varying(255) NOT NULL,
    parent_phone character varying(255) NOT NULL,
    parent_email character varying(255) NOT NULL,
    messenger_max character varying(255),
    messenger_telegram character varying(255),
    recipient_is_customer boolean DEFAULT true NOT NULL,
    recipient_name character varying(255),
    recipient_phone character varying(255) NOT NULL,
    terms_accepted boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    total_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    yookassa_payment_id character varying(255),
    yookassa_payment_status character varying(64)
);


ALTER TABLE public.orders OWNER TO "user";

--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.orders_id_seq OWNER TO "user";

--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO "user";

--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO "user";

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_access_tokens_id_seq OWNER TO "user";

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    category character varying(50) NOT NULL,
    gender character varying(10) NOT NULL,
    season character varying(50),
    price numeric(10,2) NOT NULL,
    original_price numeric(10,2),
    color character varying(50),
    image character varying(255),
    description text,
    in_stock boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.products OWNER TO "user";

--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.products_id_seq OWNER TO "user";

--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO "user";

--
-- Name: users; Type: TABLE; Schema: public; Owner: user
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(32) DEFAULT 'user'::character varying NOT NULL
);


ALTER TABLE public.users OWNER TO "user";

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: user
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO "user";

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: user
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: order_items id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.order_items ALTER COLUMN id SET DEFAULT nextval('public.order_items_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_03_26_142442_create_products_table	2
5	2026_03_28_000001_create_personal_access_tokens_table	3
6	2026_03_28_120000_create_orders_table	4
7	2026_03_28_120001_create_order_items_table	4
8	2026_03_30_120000_add_role_to_users_table	5
9	2026_04_01_120000_add_total_amount_to_orders_table	6
10	2026_04_05_120000_add_yookassa_fields_to_orders_table	7
\.


--
-- Data for Name: order_items; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.order_items (id, order_id, "position", product_name, quantity, size_override, line_comment, created_at, updated_at) FROM stdin;
4	3	0	ffff	1	fff	ff	2026-03-30 09:01:48	2026-03-30 09:01:48
5	4	0	Комплект №1	1	\N	\N	2026-04-01 11:19:39	2026-04-01 11:19:39
6	5	0	Комплект №1	1	\N	\N	2026-04-03 09:02:45	2026-04-03 09:02:45
7	5	1	Комплект №2	2	\N	\N	2026-04-03 09:02:45	2026-04-03 09:02:45
16	6	0	Комплект №1	3	\N	ааааа	2026-04-04 08:07:19	2026-04-04 08:07:19
17	6	1	Комплект №2	1	\N	\N	2026-04-04 08:07:19	2026-04-04 08:07:19
18	7	0	Комплект №1	1	42	\N	2026-04-04 09:15:31	2026-04-04 09:15:31
19	8	0	Комплект №1	1	tt	\N	2026-04-05 12:15:08	2026-04-05 12:15:08
20	9	0	Комплект №1	1	t	\N	2026-04-05 12:18:24	2026-04-05 12:18:24
21	10	0	Комплект №1	1	\N	\N	2026-04-05 12:23:37	2026-04-05 12:23:37
22	11	0	Комплект №2	1	\N	\N	2026-04-05 12:28:40	2026-04-05 12:28:40
23	12	0	Комплект №1	1	\N	\N	2026-04-05 12:31:33	2026-04-05 12:31:33
24	13	0	Комплект №1	1	\N	\N	2026-04-05 12:39:45	2026-04-05 12:39:45
25	14	0	Комплект №1	1	\N	\N	2026-04-05 12:39:58	2026-04-05 12:39:58
26	15	0	Комплект №1	1	\N	\N	2026-04-05 12:40:10	2026-04-05 12:40:10
27	16	0	Комплект №1	1	\N	\N	2026-04-05 12:45:41	2026-04-05 12:45:41
28	17	0	Комплект №1	1	\N	\N	2026-04-05 12:50:05	2026-04-05 12:50:05
29	18	0	Комплект №1	1	\N	\N	2026-04-05 13:42:34	2026-04-05 13:42:34
30	19	0	Комплект №1	1	\N	\N	2026-04-06 12:10:44	2026-04-06 12:10:44
\.


--
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.orders (id, user_id, status, child_full_name, child_gender, settlement, school, class_num, class_letter, school_year, size_from_table, height_cm, chest_cm, waist_cm, hips_cm, figure_comment, kit_comment, parent_full_name, parent_phone, parent_email, messenger_max, messenger_telegram, recipient_is_customer, recipient_name, recipient_phone, terms_accepted, created_at, updated_at, total_amount, yookassa_payment_id, yookassa_payment_status) FROM stdin;
3	8	pending	fffffff	boy	fffff	fff	fff	f	ff	ffff	fff	fff	fff	ff	fff	fff	ffff	+7911111	test@mail.ru	\N	\N	t	\N	+7911111	t	2026-03-30 09:01:48	2026-03-30 14:15:15	0.00	\N	\N
4	8	confirmed	Иванов Иван Иванович	boy	Иваново	№2	5	А	2025/2026	S	150	40	40	40	\N	Пиджак сделать черного цвета	Иванов Макс Макс	+79141501123	test@mail.ru	\N	\N	t	\N	+79141501123	t	2026-04-01 11:19:39	2026-04-01 11:51:56	0.00	\N	\N
5	14	pending	Андреева Настя Андреевич	girl	Чита	№50	5	А	2026/2027	40	150	40	40	40	\N	\N	Андреев Андрей Андреевич	+79152101412		\N	\N	t	\N	+79152101412	t	2026-04-03 09:02:45	2026-04-03 09:02:45	300.00	\N	\N
8	8	pending	test	boy	test	tset	tt	t	tt	tt	tt	t	t	t	\N	\N	tt	ttt		\N	\N	t	\N	ttt	t	2026-04-05 12:15:08	2026-04-05 12:15:09	200.00	3164660d-000f-5000-8000-119fe293175a	pending
6	15	completed	дугарова баирма будаевна	girl	Забайкальский край, Могойтуйский район, с.Ушарбай	СОШ УШАРБАЙ	7	А	2026/2027	46	140	74	74	90	большой ребенок, примите к сведению. должно быть в пору, не должно маломерить!	\N	Цыденова Дугарма Алдаровна	+79562849412	dugarma@mail.ru	\N	@dugarmaMomOfBAIRMA	t	\N	+79562849412	t	2026-04-03 09:20:01	2026-04-04 08:35:24	400.00	\N	\N
7	15	pending	Иванов Иван Иванович	boy	г. Чита	№50	4	А	2027/2028	42	150	50	50	50	\N	\N	Иванов Макс Иванович	+79141222322		\N	\N	t	\N	+79141222322	t	2026-04-04 09:15:31	2026-04-04 09:15:32	200.00	3162ea74-000f-5000-b000-10de90488b87	pending
9	8	pending	tt	boy	tt	tt	tt	t	t	tt	t	t	t	t	\N	\N	t	t		\N	\N	t	\N	t	t	2026-04-05 12:18:24	2026-04-05 12:18:24	200.00	316466d0-000f-5001-9000-11dbc003b79d	pending
10	8	pending	ff	boy	ff	f	f	f	f	f	f	f	f	f	\N	\N	f	f		\N	\N	t	\N	f	t	2026-04-05 12:23:37	2026-04-05 12:23:37	200.00	31646809-000f-5000-b000-1d28be6b9113	pending
11	8	pending	f	girl	f	f	f	f	f	f	f	f	f	f	\N	\N	f	f		\N	\N	t	\N	f	t	2026-04-05 12:28:40	2026-04-05 12:28:40	100.00	31646938-000f-5001-8000-10ddfbe0e3ba	pending
12	8	pending	f	girl	f	f	f	f	f	f	f	f	f	f	\N	\N	f	f		\N	\N	t	\N	f	t	2026-04-05 12:31:33	2026-04-05 12:31:33	100.00	316469e5-000f-5000-8000-1586b45e9060	pending
13	8	pending	rr	boy	r	r	r	r	r	r	r	r	r	r	\N	\N	r	r	test@mail.ru	\N	\N	t	\N	r	t	2026-04-05 12:39:45	2026-04-05 12:39:45	200.00	31646bd1-000f-5000-8000-1887993d3345	pending
14	8	pending	rr	boy	r	r	r	r	r	r	r	r	r	r	\N	\N	r	r	test@mail.ru	\N	\N	t	\N	r	t	2026-04-05 12:39:58	2026-04-05 12:39:59	200.00	31646bdf-000f-5001-8000-1228a20aaa3b	pending
15	8	pending	rr	boy	r	r	r	r	r	r	r	r	r	r	\N	\N	r	r	test@mail.ru	\N	\N	t	\N	r	t	2026-04-05 12:40:10	2026-04-05 12:40:10	200.00	31646bea-000f-5001-8000-1876ab2c7984	pending
16	8	pending	е	girl	е	е	е	е	е	е	е	е	е	е	\N	\N	е	е	test@mail.ru	\N	\N	t	\N	е	t	2026-04-05 12:45:41	2026-04-05 12:45:41	100.00	31646d35-000f-5001-9000-1c89bf871e68	pending
17	8	pending	е	girl	е	е	е	е	е	е	е	е	е	е	\N	\N	е	е	test@mail.ru	\N	\N	t	\N	е	t	2026-04-05 12:50:05	2026-04-05 12:50:06	100.00	31646e3e-000f-5001-8000-16117f10cc31	pending
18	8	confirmed	аа	boy	аа	аа	аа	аа	аа	а	а	а	а	а	\N	\N	а	а	test@mail.ru	\N	\N	t	\N	а	t	2026-04-05 13:42:34	2026-04-06 12:09:35	200.00	31647a8b-000f-5001-9000-17a9a20e054e	pending
19	8	pending	f	girl	f	f	f	f	f	f	f	f	f	f	\N	\N	f	f	test@mail.ru	\N	\N	t	\N	f	t	2026-04-06 12:10:44	2026-04-06 12:10:45	100.00	3165b686-000f-5000-8000-16e9eb44c9de	pending
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
1	App\\Models\\User	1	auth	30772830217a92719cc9f390de95973296eefef4fc850e3eac5a0a105aa54926	["*"]	\N	\N	2026-03-27 17:40:59	2026-03-27 17:40:59
2	App\\Models\\User	2	auth	10454aec885160aa9259826eead92303182abec282a51caa95ad1ed83d6230e1	["*"]	\N	\N	2026-03-27 17:44:54	2026-03-27 17:44:54
4	App\\Models\\User	2	auth	85e92a4e7dd2ef600b96b499f0e0ebb1d5e472c613f20c4339ac228103da69cc	["*"]	\N	\N	2026-03-27 17:52:11	2026-03-27 17:52:11
5	App\\Models\\User	4	auth	f8788c71aac39b29c4b1607145d374253c813682cdf285c8c6cdc4dd372df979	["*"]	\N	\N	2026-03-27 18:02:28	2026-03-27 18:02:28
8	App\\Models\\User	5	auth	2bb226434c62d940f6cae70635bb828f845e059f569531b19ee2e809c0d26239	["*"]	\N	\N	2026-03-27 18:30:42	2026-03-27 18:30:42
51	App\\Models\\User	8	auth	b33231d7ee74c56373b9f27eb5a1a002209914771aca66619723f8906c4ca942	["*"]	2026-04-06 12:08:02	\N	2026-04-05 11:02:21	2026-04-06 12:08:02
14	App\\Models\\User	5	auth	7694ca3785cb93854dba595536fe4f6371f930a424aef2750b2105cbc33d52b5	["*"]	\N	\N	2026-03-30 08:19:42	2026-03-30 08:19:42
47	App\\Models\\User	15	auth	ed854e4b2763cf6740f74904c742975532173d24c884cae05da390cba9ef9b82	["*"]	2026-04-03 09:07:01	\N	2026-04-03 09:07:01	2026-04-03 09:07:01
17	App\\Models\\User	6	auth	3c82809bebfd66158661767c46e176f6f55a95dcc6deb53b004993e799c86f37	["*"]	\N	\N	2026-03-30 08:24:13	2026-03-30 08:24:13
18	App\\Models\\User	7	auth	ea74f112c527a984a1fbaca6f52a0ccf2cac020436ce767f5a34820b046a0f67	["*"]	\N	\N	2026-03-30 08:24:39	2026-03-30 08:24:39
45	App\\Models\\User	14	auth	4e53ce9dab556eab695b571a3b178a5dcd80216ba56e0cd9edbd959bb4315f01	["*"]	2026-04-03 07:03:54	\N	2026-04-03 07:03:54	2026-04-03 07:03:54
21	App\\Models\\User	8	auth	a2e4ffb535dd6b6fa1770c62202e7ac35627bb68e2c3e08e9cc33666a841d130	["*"]	\N	\N	2026-03-30 08:44:45	2026-03-30 08:44:45
25	App\\Models\\User	9	auth	313f515e0849be3764b336fcd9df4efc2927b780b4ecac1d752f16c6952bda1b	["*"]	\N	\N	2026-03-30 09:00:21	2026-03-30 09:00:21
50	App\\Models\\User	15	auth	572b3c7925d8d098ffd65479bb294d4e4f86c83935dbfe08f5af64e7168f65cc	["*"]	2026-04-05 14:46:29	\N	2026-04-04 07:48:58	2026-04-05 14:46:29
53	App\\Models\\User	8	auth	e067ac01131066f83a7a097578fc2a9a6583359b0cf0cd23bfd25847aba153dc	["*"]	2026-04-06 12:10:27	\N	2026-04-06 12:10:24	2026-04-06 12:10:27
34	App\\Models\\User	10	auth	0e5aef6ecc04c8f5e819f15c9bf97e98f1a3b39e14e77d31469e0da2dc8c4b0e	["*"]	\N	\N	2026-03-30 12:56:37	2026-03-30 12:56:37
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.products (id, name, category, gender, season, price, original_price, color, image, description, in_stock, created_at, updated_at) FROM stdin;
1	Брюки 	Брюки	girls	20/21	100.00	100.00	оранжевый	https://images.pexels.com/photos/5566721/pexels-photo-5566721.jpeg	Брюки льняные	t	\N	\N
2	Юбка	Юбки	girls	21/22	200.00	250.00	белый	https://images.pexels.com/photos/4663319/pexels-photo-4663319.jpeg	Юбка летняя	t	\N	\N
3	Кардиган	Кардиганы	boys	20/21	150.00	150.00	черный	https://images.pexels.com/photos/14790239/pexels-photo-14790239.jpeg	Кардиган летний	t	\N	\N
6	Комплект №1	Комплекты	boys	2025/2026	200.00	200.00	синий	https://avatars.mds.yandex.net/get-mpic/12168282/2a000001910f953c31608512f50372eba1e0/optimize	Пиджак, брюки	t	\N	\N
7	Комплект №2	Комплекты	boys	2025/2026	200.00	200.00	синий	https://avatars.mds.yandex.net/get-mpic/16154795/2a0000019ac5159f32486c893df306b04c13/optimize	Жилет, брюки	t	\N	\N
4	Комплект №1	Комплекты	girls	2025/2026	100.00	200.00	синий	https://ir.ozone.ru/s3/multimedia-1-s/wc1000/7693174504.jpg	Жилет, юбка	t	\N	\N
5	Комплект №2	Комплекты	girls	2025/2026	100.00	200.00	коричневый	https://pkf-uspeh.ru/image/cache/data/new/Komplekt/roza/IMG_4479-207x310.jpg	Сарафан	t	\N	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: user
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role) FROM stdin;
9	еуые еуые	test1@mail.ru	\N	$2y$12$vtJu12lj527eYogm82a4ZupHHvC.uPmAQ2avj6DfZRhTilyOUgiNC	\N	2026-03-30 09:00:21	2026-03-30 09:00:21	user
8	Max max	test@mail.ru	\N	$2y$12$68YqZL7X1hLXKEYssaJeGu.Jm1LLhvW4IDCRE1j7e48FpYJOXcu/q	\N	2026-03-30 08:44:45	2026-03-30 12:47:01	user
10	Aaaaa aaaaa	test2@mail.ru	\N	$2y$12$3T7ruMEeys3LQrFE1UcUneaAb./ovzJ.ZUh.tKp/2qpYpejA5SSF.	\N	2026-03-30 12:56:37	2026-03-30 12:56:37	user
11	Главный админ	admin@zabruno.local	\N	$2y$12$DTU.U0QdTy8tipp9mCPzsOMijOI.7oWpHELiwH4D3eTxxlp.cJ0qi	\N	2026-03-30 13:41:18	2026-03-30 13:41:18	super_admin
12	Test User	test@example.com	\N	$2y$12$dQFUw1sKlDs2pvEf6YsWQeA/s4gui8x60dHPJ3KfkNXWq1bEIUG.O	\N	2026-03-30 13:41:18	2026-03-30 13:41:18	user
13	admin	admin@mail.ru	\N	$2y$12$1CQKrCqE9qPTyfs.NJS7a.uWxDry4qjs96cTHz4jenP4ndrnw65bC	\N	2026-03-30 13:44:29	2026-03-30 13:44:29	admin
14	max max	max@mail.ru	\N	$2y$12$b7Di.RdyQ4j5BwCahl1i2.BS03dYsYfnuTewre5Oa3ruwWxlLPE.S	\N	2026-04-03 07:03:54	2026-04-03 07:03:54	user
15	дугарма цыденова	dugarma@mail.ru	\N	$2y$12$wdRSW9gSuoa.6Cu.HQq.uuHXNmaiYS2giWnrQMOzCtSgfygbG0Msq	\N	2026-04-03 09:07:01	2026-04-03 09:07:01	user
\.


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 10, true);


--
-- Name: order_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.order_items_id_seq', 30, true);


--
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.orders_id_seq', 19, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 53, true);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.products_id_seq', 7, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: user
--

SELECT pg_catalog.setval('public.users_id_seq', 15, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (id);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: order_items_order_id_position_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX order_items_order_id_position_index ON public.order_items USING btree (order_id, "position");


--
-- Name: orders_created_at_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX orders_created_at_index ON public.orders USING btree (created_at);


--
-- Name: orders_status_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX orders_status_index ON public.orders USING btree (status);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: users_role_index; Type: INDEX; Schema: public; Owner: user
--

CREATE INDEX users_role_index ON public.users USING btree (role);


--
-- Name: order_items order_items_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict SyOStcySHhaYMGVEoabZWE32Fyz5nVq10Nx7acRLuQMzYiVVjKY1XuyOdLZitYL

