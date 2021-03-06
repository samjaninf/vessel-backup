#ifndef CLIENT_H
#define CLIENT_H

#include <boost/program_options.hpp>
#include <boost/date_time/posix_time/posix_time.hpp>
#include <boost/thread.hpp>
#include <boost/thread/thread.hpp>
#include <boost/asio/io_service.hpp>
#include <boost/bind.hpp>

#include <vessel/database/local_db.hpp>
#include <vessel/log/log.hpp>
#include <vessel/aws/aws_s3_client.hpp>
#include <vessel/azure/azure_client.hpp>
#include <vessel/vessel/vessel_client.hpp>
#include <vessel/vessel/queue_manager.hpp>
#include <vessel/filesystem/directory.hpp>
#include <vessel/filesystem/file_iterator.hpp>
#include <vessel/vessel/upload_manager.hpp>
#include <vessel/vessel/stat_manager.hpp>
#include <vessel/vessel/app_manager.hpp>

using namespace Vessel;
using namespace Vessel::Types;
using namespace Vessel::Database;
using namespace Vessel::Networking;

#endif
