#ifndef HTTPCLIENT_H
#define HTTPCLIENT_H

#include <iostream>
#include <cctype>
#include <iomanip>
#include <string>
#include <regex>
#include <memory>
#include <map>

#include <boost/asio.hpp>
#include <boost/asio/ssl.hpp>
#include <boost/bind.hpp>
#include <boost/iostreams/stream.hpp>
#include <boost/algorithm/string.hpp>

#include <vessel/database/local_db.hpp>
#include <vessel/log/log.hpp>
#include <vessel/network/http_stream.hpp>
#include <vessel/network/http_exception.hpp>
#include <vessel/network/http_request.hpp>

using namespace Vessel::Exception;
using namespace Vessel::Logging;

namespace Vessel {

    namespace Networking {

        class HttpClient
        {

            public:

                HttpClient( const std::string& uri );
                ~HttpClient();

                 /*! \fn bool is_connected();
                    \brief Return whether or not the client is currently connected
                    \return Return whether or not the client is currently connected
                */
                bool is_connected();

                /*! \fn void set_timeout( boost::posix_time::time_duration t );
                    \brief Sets the connection timeout
                */
                void set_timeout( boost::posix_time::time_duration t );

                /*! \fn void set_ssl(bool f);
                    \brief Set whether or not to use SSL when connecting. Automatically determined from URL typically
                */
                void set_ssl(bool f);

                /*! \fn std::string get_response();
                    \brief Returns the HTTP response payload
                    \return Returns the HTTP response payload
                */
                std::string get_response();

                /*! \fn std::string get_headers();
                    \brief Returns the HTTP headers returned in the response
                    \return Returns the HTTP headers returned in the response
                */
                std::string get_headers();

                /*! \fn std::string get_header(const std::string& key);
                    \brief Returns the specified HTTP header value (if exists)
                    \return Returns the specified HTTP header value (if exists)
                */
                std::string get_header(const std::string& key);

                /*! \fn unsigned int get_http_status();
                    \brief Returns the HTTP status code (eg. 200) of the response
                    \return Returns the HTTP status code (eg. 200) of the response
                */
                unsigned int get_http_status();

                /*! \fn std::string get_error();
                    \brief Returns the most recent error message recorded from the last operation
                    \return Returns the most recent error message recorded from the last operation
                */
                std::string get_error();

                /*! \fn std::string get_hostname();
                    \brief Returns the hostname of the URI
                    \return Returns the hostname of the URI
                */
                std::string get_hostname();

                /*! \fn bool is_https();
                    \brief Returns whether or not the connection uses HTTPS (secure)
                    \return Returns whether or not the connection uses HTTP (secure)
                */
                bool is_https();

                boost::system::error_code get_error_code();

                /*! \fn std::string encode_url(const std::string& url);
                    \brief Encodes URL according to RFC 3986. Spaces are encoded to "%20"
                    \return Returns the URL encoded string
                */
                std::string encode_uri(const std::string& uri);

                //TBD
                std::string decode_uri(const std::string& uri);

                /*! \fn int send_http_request ( const HttpRequest& request );
                    \brief Sends a new HTTP request and writes to the socket
                    \return HTTP status code
                */
                int send_http_request ( const HttpRequest& request );

                /*! \fn bool http_logging();
                    \brief Returns true if http logging is enabled
                    \return True if http logging is enabled
                */
                bool http_logging();

                /*! \fn static void http_logging(bool flag);
                    \brief Enables or disables HTTP logging
                */
                static void http_logging(bool flag);

            private:

                Vessel::Database::LocalDatabase* m_ldb;
                std::string m_hostname;
                std::string m_protocol;
                unsigned int m_port; //or service name
                bool m_connected;
                bool m_use_ssl;
                boost::asio::ssl::context m_ssl_ctx;
                boost::posix_time::time_duration m_timeout;
                bool m_verify_cert;
                boost::asio::io_service m_io_service;
                std::shared_ptr<boost::asio::ip::tcp::socket> m_socket;
                std::shared_ptr<boost::asio::ssl::stream<boost::asio::ip::tcp::socket>> m_ssl_socket;
                std::shared_ptr<boost::asio::deadline_timer> m_deadline_timer;

                //Member Vars for Data Transfer
                std::shared_ptr<boost::asio::streambuf> m_response_buffer;
                std::string m_request_data; //Response Data

                unsigned int m_http_status;
                std::string m_header_data;
                std::map<std::string,std::string> m_response_headers;
                std::string m_response_data;
                boost::system::error_code m_conn_status;
                bool m_ssl_good;
                boost::system::error_code m_response_ec;
                bool m_chunked_encoding;

                void parse_url(const std::string& host );

                //Async function which persistently checks if the connection should timeout
                void check_deadline();

                void handle_connect( const boost::system::error_code& e );
                void handle_handshake(const boost::system::error_code& e );
                void handle_response( const boost::system::error_code& e );
                void handle_write( const boost::system::error_code& e, size_t bytes_transferred );
                void handle_read_content( const boost::system::error_code& e, size_t bytes_transferred );
                void handle_read_headers( const boost::system::error_code& e );
                void read_chunked_content( const boost::system::error_code& e, size_t bytes_transferred );
                void read_buffer_data();

                void cleanup();
                void set_defaults();

                /** Logging and errors **/
                Vessel::Logging::Log* m_log;
                std::string m_error_message;

                static bool m_http_logging;

            protected:

                /*! \fn bool connect();
                    \brief Connect to the master server
                */
                bool connect();

                /*! \fn bool disconnect();
                    \brief Disconnect from the master server
                */
                void disconnect();

                void set_error(const std::string& msg);

                void clear_response();
                void clear_headers();

                void set_deadline(long seconds);

                void clear_error_code();

                void write_socket( const std::string& str );

                void run_io_service();

        };




    }

}

#endif
