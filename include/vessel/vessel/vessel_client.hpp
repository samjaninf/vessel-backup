#ifndef VESSELCLIENT_H
#define VESSELCLIENT_H

#include <iostream>
#include <string>
#include <sstream>
#include <vector>
#include <map>
#include <boost/array.hpp>
#include <boost/lambda/lambda.hpp>
#include <boost/algorithm/string.hpp>

//CryptoPP
#include <cryptopp/base64.h>

//RapidJSON
#include <rapidjson/document.h>
#include <rapidjson/prettywriter.h>
#include <rapidjson/stringbuffer.h>
#include <rapidjson/error/en.h>

#ifdef _WIN32
    #include <winsock2.h>
#endif // _WIN32

//Custom includes
#include <vessel/types.hpp>
#include <vessel/database/local_db.hpp>
#include <vessel/compression/compress.hpp>
#include <vessel/network/http_request.hpp>
#include <vessel/filesystem/file.hpp>
#include <vessel/filesystem/directory.hpp>
#include <vessel/log/log.hpp>
#include <vessel/network/http_client.hpp>
#include <vessel/vessel/vessel_exception.hpp>

//#define BOOST_NETWORK_ENABLE_HTTPS 1

using boost::asio::ip::tcp;
using boost::asio::deadline_timer;

using namespace Vessel;
using namespace Vessel::Types;
using namespace Vessel::File;
using namespace Vessel::Database;
using namespace Vessel::Logging;
using namespace rapidjson;

namespace ssl = boost::asio::ssl;

namespace Vessel {
    namespace Networking {

        class VesselClient : public HttpClient
        {

            enum { buffer_size = 1024 };

            public:

                VesselClient( const std::string& hostname );

                /*! \fn std::string init_upload( const Vessel::File::BackupFile& bf );
                    \brief Initialize a new file upload with the Vessel REST API
                    \return Returns the Vessel file id from the API
                */
                std::string init_upload( const Vessel::File::BackupFile& bf );

                /*! \fn void complete_upload( const std::string& upload_id );
                    \brief Marks an upload as completed via the Vessel API
                */
                void complete_upload( const std::string& upload_id );

                /*! \fn upload_file_part( Vessel::File::BackupFile * bf, int part_number );
                    \brief Sends part of a file (or the entire file) to the server with metadata
                    \param bf BackupFile object
                    \param part_number The part or chunk number of the file content being uploaded
                    \return Returns true if successful, false if there were errors
                */
                bool upload_file_part( Vessel::File::BackupFile * bf, int part_number );

                /*! \fn void heartbeat();
                    \brief Sends a heartbeat payload to the Vessel API. API returns client settings and other data back to the client
                */
                void heartbeat();

                /*! \fn bool has_deployment_key();
                    \brief Returns true if the deployment_key setting has been set
                    \return Returns true if the deployment_key setting has been set
                */
                bool has_deployment_key();

                /*! \fn bool has_client_token();
                    \brief Returns true if the client_token setting has been set
                    \return Returns true if the client_token setting has been set
                */
                bool has_client_token();

                /*! \fn void install_client();
                    \brief Installs and initializes the client application with the Vessel API using the deployment key
                */
                void install_client();

                /*! \fn StorageProvider get_storage_provider();
                    \brief Returns the highest priority storage provider
                    \return Returns the highest priority storage provider
                */
                StorageProvider get_storage_provider();

                /*! \fn void set_client_token();
                    \brief Refreshes the client token from the database
                */
                void refresh_client_token();

            private:

                LocalDatabase* m_ldb;
                Log* m_log;

                std::string get_auth_header(const std::string& token, const std::string& user_id);

                std::string m_auth_header;
                std::string m_auth_token;
                std::string m_client_token;
                std::string m_api_path;
                std::string m_user_id;

                /*! \fn bool sync_storage_provider(const Value& obj);
                    \brief Syncs a single storage provider to the local database
                */
                bool sync_storage_provider(const Value& obj);

                /*! \fn bool sync_storage_provider_all(const std::vector<std::string>& provider_ids);
                    \brief Compares a vector of provider_ids to the local database and removes obsolete providers
                */
                void sync_storage_provider_all(const std::vector<std::string>& provider_ids);

                /*! \fn void delete_storage_provider(const std::string& id );
                    \brief Removes a storage provider from the local database
                */
                bool delete_storage_provider(const std::string& id );

                /*! \fn std::string get_provider_endpoint(const std::string& provider_type);
                    \brief Returns the provider API endpoint
                */
                std::string get_provider_endpoint(const std::string& provider_type);

        };

    }

}



#endif // VESSELCLIENT_H
